<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * dial_locks — prevents two agents from dialling the same number at once.
 *
 * One active row per phone number (keyed by the last-10 digits so different
 * formats of the same number collide). A lock is created when an agent starts
 * a call and is removed when the call is logged or when it expires.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dial_locks')) {
            return;
        }

        Schema::create('dial_locks', function (Blueprint $table) {
            $table->id();
            $table->string('phone_key', 20)->unique();      // normalised number (last 10 digits)
            $table->string('full_number', 30);              // what the agent actually clicked
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 255)->nullable();   // cached for display in the block message
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dial_locks');
    }
};
