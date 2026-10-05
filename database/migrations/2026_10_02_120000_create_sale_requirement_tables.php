<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Horsefly\SaleRequirementField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // One row per sales column composed by the sale form (timing, experience, benefits, qualification)
        Schema::create('sale_requirement_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('label', 100);
            $table->string('icon', 100)->nullable();
            $table->string('hint')->nullable();
            $table->string('prefix')->nullable();
            $table->string('connector', 100)->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('show_hours')->default(false);
            $table->timestamps();
        });

        Schema::create('sale_requirement_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_id')->constrained('sale_requirement_fields')->cascadeOnDelete();
            $table->string('title', 100);
            $table->boolean('is_single')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('sale_requirement_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('sale_requirement_groups')->cascadeOnDelete();
            $table->string('label', 150);
            $table->string('text')->nullable();
            $table->string('connector', 100)->nullable();
            $table->string('suffix', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        SaleRequirementField::seedDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_requirement_options');
        Schema::dropIfExists('sale_requirement_groups');
        Schema::dropIfExists('sale_requirement_fields');
    }
};
