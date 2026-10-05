<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Multi-select groups can lead their chips with words and an optional number, e.g. "3 double shift 10 am to 11:30 pm"
        Schema::table('sale_requirement_groups', function (Blueprint $table) {
            $table->string('prefix', 150)->nullable()->after('is_single');
            $table->boolean('has_quantity')->default(false)->after('prefix');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_requirement_groups', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'has_quantity']);
        });
    }
};
