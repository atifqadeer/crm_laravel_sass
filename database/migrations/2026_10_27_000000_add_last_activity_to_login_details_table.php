<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attendance check-out when the user never logs out (PC shut down, browser closed):
 *  - last_activity_at: last time the portal was in use (login, heartbeat while the user is active, logout);
 *  - checkout_source: 'logout' when the user logged out, 'auto' when logout_at was filled from
 *    last_activity_at by the attendance:auto-checkout command (null on rows from before this change).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_details', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('logout_at');
            $table->string('checkout_source', 10)->nullable()->after('last_activity_at');
            $table->index(['user_id', 'created_at'], 'idx_login_details_user_created');
        });
    }

    public function down(): void
    {
        Schema::table('login_details', function (Blueprint $table) {
            $table->dropIndex('idx_login_details_user_created');
            $table->dropColumn(['last_activity_at', 'checkout_source']);
        });
    }
};
