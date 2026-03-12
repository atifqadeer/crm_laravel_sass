<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // sales — JOIN and filter columns
        // ---------------------------------------------------------------
        Schema::table('sales', function (Blueprint $table) {
            // Composite index: status filter (the most common WHERE clause)
            if (!$this->indexExists('sales', 'idx_sales_status_hold')) {
                $table->index(['status', 'is_on_hold', 'updated_at'], 'idx_sales_status_hold');
            }
            // JOIN targets
            if (!$this->indexExists('sales', 'idx_sales_office_id')) {
                $table->index('office_id', 'idx_sales_office_id');
            }
            if (!$this->indexExists('sales', 'idx_sales_unit_id')) {
                $table->index('unit_id', 'idx_sales_unit_id');
            }
            if (!$this->indexExists('sales', 'idx_sales_user_id')) {
                $table->index('user_id', 'idx_sales_user_id');
            }
            if (!$this->indexExists('sales', 'idx_sales_job_title_id')) {
                $table->index('job_title_id', 'idx_sales_job_title_id');
            }
            if (!$this->indexExists('sales', 'idx_sales_job_category_id')) {
                $table->index('job_category_id', 'idx_sales_job_category_id');
            }
            // LIKE search on postcode
            if (!$this->indexExists('sales', 'idx_sales_postcode')) {
                $table->index('sale_postcode', 'idx_sales_postcode');
            }
        });

        // ---------------------------------------------------------------
        // audits — correlated subquery: auditable_type + auditable_id + message + id
        // ---------------------------------------------------------------
        Schema::table('audits', function (Blueprint $table) {
            if (!$this->indexExists('audits', 'idx_audits_type_id_msg')) {
                $table->index(['auditable_type', 'auditable_id', 'message', 'id'], 'idx_audits_type_id_msg');
            }
        });

        // ---------------------------------------------------------------
        // cv_notes — GROUP BY aggregation + status filter
        // ---------------------------------------------------------------
        Schema::table('cv_notes', function (Blueprint $table) {
            if (!$this->indexExists('cv_notes', 'idx_cv_notes_sale_status')) {
                $table->index(['sale_id', 'status'], 'idx_cv_notes_sale_status');
            }
        });

        // ---------------------------------------------------------------
        // offices / units — JOIN targets (office_name LIKE search)
        // ---------------------------------------------------------------
        Schema::table('offices', function (Blueprint $table) {
            if (!$this->indexExists('offices', 'idx_offices_name')) {
                $table->index('office_name', 'idx_offices_name');
            }
        });

        Schema::table('units', function (Blueprint $table) {
            if (!$this->indexExists('units', 'idx_units_name')) {
                $table->index('unit_name', 'idx_units_name');
            }
        });

        // ---------------------------------------------------------------
        // users — JOIN target (name LIKE search)
        // ---------------------------------------------------------------
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_name')) {
                $table->index('name', 'idx_users_name');
            }
        });

        // ---------------------------------------------------------------
        // job_titles / job_categories — JOIN + LIKE search on name
        // ---------------------------------------------------------------
        Schema::table('job_titles', function (Blueprint $table) {
            if (!$this->indexExists('job_titles', 'idx_job_titles_name')) {
                $table->index('name', 'idx_job_titles_name');
            }
        });

        Schema::table('job_categories', function (Blueprint $table) {
            if (!$this->indexExists('job_categories', 'idx_job_categories_name')) {
                $table->index('name', 'idx_job_categories_name');
            }
        });
    }

    public function down(): void
    {
        $drops = [
            'sales'          => ['idx_sales_status_hold', 'idx_sales_office_id', 'idx_sales_unit_id', 'idx_sales_user_id', 'idx_sales_job_title_id', 'idx_sales_job_category_id', 'idx_sales_postcode'],
            'audits'         => ['idx_audits_type_id_msg'],
            'cv_notes'       => ['idx_cv_notes_sale_status'],
            'offices'        => ['idx_offices_name'],
            'units'          => ['idx_units_name'],
            'users'          => ['idx_users_name'],
            'job_titles'     => ['idx_job_titles_name'],
            'job_categories' => ['idx_job_categories_name'],
        ];

        foreach ($drops as $table => $indexes) {
            Schema::table($table, function (Blueprint $t) use ($indexes) {
                foreach ($indexes as $idx) {
                    try { $t->dropIndex($idx); } catch (\Exception $e) { /* already gone */ }
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};
