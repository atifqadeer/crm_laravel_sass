<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PERMISSION = 'administrator-applicant-match-api-index';
    private const LOCATION_INDEX = 'idx_applicants_lat_lng';
    private const API_KEY_SETTING = 'api_key_active_sales';
    private const AI_API_KEY_SETTING = 'api_key_ai_automation';

    public function up(): void
    {
        // 1. Permission for the Administrator → Applicant Match API page.
        $permissionTable = config('permission.table_names.permissions', 'permissions');
        $guardName = config('auth.defaults.guard', 'web');

        $exists = DB::table($permissionTable)
            ->where('name', self::PERMISSION)
            ->where('guard_name', $guardName)
            ->exists();

        if (!$exists) {
            DB::table($permissionTable)->insert([
                'name' => self::PERMISSION,
                'guard_name' => $guardName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        // 2. Portal API key moves to settings ("api_key_active_sales").
        //    Copy the key from config/services.php so existing callers keep
        //    working with the same key; generate one if it isn't there.
        if (!DB::table('settings')->where('key', self::API_KEY_SETTING)->exists()) {
            $existing = config('services.portal.token');

            DB::table('settings')->insert([
                'key' => self::API_KEY_SETTING,
                'value' => is_string($existing) && $existing !== '' ? $existing : Str::random(48),
                'type' => 'string',
                'group' => 'api',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Separate key for the applicant job-matches + update APIs
        //    ("api_key_ai_automation"). New integration, so a fresh random key.
        if (!DB::table('settings')->where('key', self::AI_API_KEY_SETTING)->exists()) {
            DB::table('settings')->insert([
                'key' => self::AI_API_KEY_SETTING,
                'value' => Str::random(48),
                'type' => 'string',
                'group' => 'api',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Lets the radius match range-scan applicants by latitude instead of
        //    reading all of them (SaleApplicantMatchService bounding box).
        if (Schema::hasTable('applicants') && !Schema::hasIndex('applicants', self::LOCATION_INDEX)) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->index(['lat', 'lng'], self::LOCATION_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicants') && Schema::hasIndex('applicants', self::LOCATION_INDEX)) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->dropIndex(self::LOCATION_INDEX);
            });
        }

        // Keep the permission (it may have been assigned to roles after migrating)
        // and the API key settings (removing them would lock out every API caller).
    }
};
