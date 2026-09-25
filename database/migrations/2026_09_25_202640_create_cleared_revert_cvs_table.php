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
        Schema::create('cleared_revert_cvs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cv_note_id');      // original cv_notes.id, for traceability
            $table->unsignedBigInteger('applicant_id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('user_id');          // original cv_notes.user_id (who requested the CV)
            $table->unsignedBigInteger('reverted_by')->nullable(); // who triggered the revert, if different
            $table->text('details')->nullable();            // the revert reason/notes
            $table->datetime('created_at')->nullable(); // original CV request date
            $table->datetime('updated_at')->nullable(); // updated_at here = revert moment

            $table->index(['applicant_id', 'sale_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleared_revert_cvs');
    }
};
