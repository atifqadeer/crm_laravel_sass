<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * dial_call_logs — per-agent, per-number, per-day call counters.
 *
 * Used to enforce the "max calls per agent per day" dial lock setting and to
 * provide a short rolling history of dialling activity. Rows older than the
 * configured retention window (dialing_history_days) are purged whenever a
 * new call is logged.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dial_call_logs')) {
            return;
        }

        Schema::create('dial_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone_key', 20);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('call_date');
            $table->unsignedInteger('calls')->default(0);
            $table->timestamps();

            $table->unique(['phone_key', 'user_id', 'call_date']);
            $table->index('call_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dial_call_logs');
    }
};
