<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('job_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the source (e.g., "LinkedIn", "Company Website")
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true); // To enable/disable the source
            
            // Timestamps with default values and automatic update on change
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Optional indexes for better performance
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_sources');
    }
};
