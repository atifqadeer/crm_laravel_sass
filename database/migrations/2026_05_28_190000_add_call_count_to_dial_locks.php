<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a cumulative per-number dial counter to dial_locks so the CRM can show
 * "this number has been called N times" and enforce timing-based duplicate
 * prevention. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('dial_locks')) {
            return;
        }
        if (!Schema::hasColumn('dial_locks', 'call_count')) {
            Schema::table('dial_locks', function (Blueprint $table) {
                $table->unsignedInteger('call_count')->default(0)->after('applicant_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dial_locks') && Schema::hasColumn('dial_locks', 'call_count')) {
            Schema::table('dial_locks', function (Blueprint $table) {
                $table->dropColumn('call_count');
            });
        }
    }
};
