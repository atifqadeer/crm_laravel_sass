<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // applicants — WHERE status = 1 (filters the entire base query)
        // ---------------------------------------------------------------
        Schema::table('applicants', function (Blueprint $table) {
            $table->index(['status', 'id'], 'idx_applicants_status_id');
        });

        // ---------------------------------------------------------------
        // quality_notes — ROW_NUMBER() PARTITION BY applicant_id, sale_id
        // ORDER BY id DESC, filtered by moved_tab_to
        // ---------------------------------------------------------------
        Schema::table('quality_notes', function (Blueprint $table) {
            $table->index(
                ['moved_tab_to', 'applicant_id', 'sale_id', 'id'],
                'idx_qn_tab_applicant_sale_id'
            );
        });

        // ---------------------------------------------------------------
        // cv_notes — ROW_NUMBER() PARTITION BY applicant_id, sale_id
        // ORDER BY id DESC/ASC, filtered by status
        // ---------------------------------------------------------------
        Schema::table('cv_notes', function (Blueprint $table) {
            $table->index(
                ['status', 'applicant_id', 'sale_id', 'id'],
                'idx_cn_status_applicant_sale_id'
            );
            // For earliestCvNoteSubquery (no status filter)
            $table->index(
                ['applicant_id', 'sale_id', 'id'],
                'idx_cn_applicant_sale_id'
            );
        });

        // ---------------------------------------------------------------
        // history — ROW_NUMBER() PARTITION BY applicant_id, sale_id
        // filtered by sub_stage + status
        // ---------------------------------------------------------------
        Schema::table('history', function (Blueprint $table) {
            $table->index(
                ['sub_stage', 'status', 'applicant_id', 'sale_id', 'id'],
                'idx_history_substage_status_applicant_sale'
            );
        });

        // ---------------------------------------------------------------
        // revert_stages — ROW_NUMBER() PARTITION BY applicant_id, sale_id
        // filtered by stage
        // ---------------------------------------------------------------
        Schema::table('revert_stages', function (Blueprint $table) {
            $table->index(
                ['stage', 'applicant_id', 'sale_id', 'id'],
                'idx_revert_stage_applicant_sale_id'
            );
        });

        // ---------------------------------------------------------------
        // sales — joined on sales.id, office_id, unit_id
        // ---------------------------------------------------------------
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['office_id', 'unit_id'], 'idx_sales_office_unit');
        });
    }

    public function down(): void
    {
        Schema::table('applicants',    fn($t) => $t->dropIndex('idx_applicants_status_id'));
        Schema::table('quality_notes', fn($t) => $t->dropIndex('idx_qn_tab_applicant_sale_id'));
        Schema::table('cv_notes',      fn($t) => $t->dropIndex('idx_cn_status_applicant_sale_id'));
        Schema::table('cv_notes',      fn($t) => $t->dropIndex('idx_cn_applicant_sale_id'));
        Schema::table('history',       fn($t) => $t->dropIndex('idx_history_substage_status_applicant_sale'));
        Schema::table('revert_stages', fn($t) => $t->dropIndex('idx_revert_stage_applicant_sale_id'));
        Schema::table('sales',         fn($t) => $t->dropIndex('idx_sales_office_unit'));
    }
};