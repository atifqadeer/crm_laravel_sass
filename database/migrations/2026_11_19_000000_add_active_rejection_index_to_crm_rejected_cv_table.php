<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'idx_crm_rejected_cv_status_applicant_sale';

    /**
     * Covers the "latest active rejection per applicant + sale" lookup used by
     * ResourceController::getResourcesRejectedApplicants():
     *   WHERE status = 1 GROUP BY applicant_id, sale_id -> MAX(id)
     * crm_note_id is included so the join to crm_notes can be read from the index too.
     */
    public function up(): void
    {
        if (! Schema::hasTable('crm_rejected_cv') || Schema::hasIndex('crm_rejected_cv', self::INDEX)) {
            return;
        }

        Schema::table('crm_rejected_cv', function (Blueprint $table) {
            $table->index(['status', 'applicant_id', 'sale_id', 'id', 'crm_note_id'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_rejected_cv') || ! Schema::hasIndex('crm_rejected_cv', self::INDEX)) {
            return;
        }

        Schema::table('crm_rejected_cv', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
