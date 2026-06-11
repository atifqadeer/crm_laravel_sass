<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds indexed, digits-only "normalized" phone columns so caller-ID lookup and
 * click-to-dial can match a number on an index instead of scanning the whole
 * applicants table with RIGHT(REGEXP_REPLACE(...)). The columns store the last
 * 10 digits of each phone field and are kept in sync by Applicant::booted().
 *
 * Idempotent: safe to run on a database that already has some of the columns.
 */
return new class extends Migration {
    public function up(): void
    {
        // 1) Columns
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'applicant_phone_normalized')) {
                $table->string('applicant_phone_normalized', 10)->nullable()->after('applicant_phone');
            }
            if (!Schema::hasColumn('applicants', 'applicant_phone_secondary_normalized')) {
                $table->string('applicant_phone_secondary_normalized', 10)->nullable()->after('applicant_phone_secondary');
            }
            if (!Schema::hasColumn('applicants', 'applicant_landline_normalized')) {
                $table->string('applicant_landline_normalized', 10)->nullable()->after('applicant_landline');
            }
        });

        // 2) Indexes
        Schema::table('applicants', function (Blueprint $table) {
            if (!$this->indexExists('applicants', 'idx_applicant_phone_normalized')) {
                $table->index('applicant_phone_normalized', 'idx_applicant_phone_normalized');
            }
            if (!$this->indexExists('applicants', 'idx_applicant_phone_secondary_normalized')) {
                $table->index('applicant_phone_secondary_normalized', 'idx_applicant_phone_secondary_normalized');
            }
            if (!$this->indexExists('applicants', 'idx_applicant_landline_normalized')) {
                $table->index('applicant_landline_normalized', 'idx_applicant_landline_normalized');
            }
        });

        // 3) Backfill existing rows (NULLIF keeps empty/blank numbers NULL,
        //    matching App\Support\PhoneNumber::normalize()).
        DB::statement("UPDATE applicants SET applicant_phone_normalized = NULLIF(RIGHT(REGEXP_REPLACE(applicant_phone, '[^0-9]', ''), 10), '')");
        DB::statement("UPDATE applicants SET applicant_phone_secondary_normalized = NULLIF(RIGHT(REGEXP_REPLACE(applicant_phone_secondary, '[^0-9]', ''), 10), '')");
        DB::statement("UPDATE applicants SET applicant_landline_normalized = NULLIF(RIGHT(REGEXP_REPLACE(applicant_landline, '[^0-9]', ''), 10), '')");
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach ([
                'idx_applicant_phone_normalized',
                'idx_applicant_phone_secondary_normalized',
                'idx_applicant_landline_normalized',
            ] as $index) {
                if ($this->indexExists('applicants', $index)) {
                    $table->dropIndex($index);
                }
            }

            foreach ([
                'applicant_phone_normalized',
                'applicant_phone_secondary_normalized',
                'applicant_landline_normalized',
            ] as $column) {
                if (Schema::hasColumn('applicants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /** Check if a named index exists on a table. */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};
