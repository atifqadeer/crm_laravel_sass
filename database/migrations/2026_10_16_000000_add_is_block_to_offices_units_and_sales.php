<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->boolean('is_blacklist')->default(false)->after('status');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->boolean('is_blacklist')->default(false)->after('status');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_blacklist')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('is_blacklist');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('is_blacklist');
        });

        Schema::table('offices', function (Blueprint $table) {
            $table->dropColumn('is_blacklist');
        });
    }
};
