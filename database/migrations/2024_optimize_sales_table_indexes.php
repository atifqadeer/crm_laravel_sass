<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds missing indexes to the sales table to optimize query performance.
     * These indexes are critical for the getScrappedSales() DataTables query.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Composite index for the main WHERE clause (status = 4, deleted_at IS NULL)
            $table->index(['status', 'deleted_at'], 'idx_sales_status_deleted');

            // Foreign key indexes for JOINs
            $table->index('job_category_id', 'idx_sales_job_category');
            $table->index('job_title_id', 'idx_sales_job_title');
            $table->index('office_id', 'idx_sales_office');
            $table->index('unit_id', 'idx_sales_unit');
            $table->index('user_id', 'idx_sales_user');

            // Sorting column indexes
            $table->index('created_at', 'idx_sales_created_at');
            $table->index('updated_at', 'idx_sales_updated_at');

            // Search optimization for Scout
            $table->fulltext('sale_postcode', 'idx_sales_postcode_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_status_deleted');
            $table->dropIndex('idx_sales_job_category');
            $table->dropIndex('idx_sales_job_title');
            $table->dropIndex('idx_sales_office');
            $table->dropIndex('idx_sales_unit');
            $table->dropIndex('idx_sales_user');
            $table->dropIndex('idx_sales_created_at');
            $table->dropIndex('idx_sales_updated_at');
            $table->dropIndex('idx_sales_postcode_fulltext');
        });
    }
};
