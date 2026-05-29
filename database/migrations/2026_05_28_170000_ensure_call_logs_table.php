<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent safety-net migration.
 *
 * The original create_call_logs_table migration was recorded as "ran" but the
 * table was never actually created (stale migration record). This migration
 * creates the table only if it is still missing, so it is safe to run on any
 * environment without dropping or deleting anything.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('call_logs')) {
            return;
        }

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('caller_number', 50)->index();
            $table->string('caller_name', 255)->nullable();
            $table->enum('direction', ['inbound', 'outbound', 'missed'])->default('outbound')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('sip_call_id', 255)->nullable()->index();
            $table->string('source', 20)->default('browser')->index(); // 'browser' | 'desktop'
            $table->timestamp('called_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally a no-op — we never want this safety net to drop the table.
    }
};
