<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attachments for head offices and units.
 *
 * Deleting a document is a soft delete: `deleted_at`, `deleted_by` and `delete_reason`
 * are set and the file stays on disk. The documents:purge-deleted command removes the
 * file and the row permanently three days later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained('offices');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_name', 255);      // original file name, shown to users
            $table->string('document_path', 255);      // relative to public/, e.g. office_docs/abc.pdf
            $table->unsignedBigInteger('document_size')->nullable(); // bytes
            $table->string('document_extension', 10)->nullable();
            $table->text('delete_reason')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['office_id', 'deleted_at']);
        });

        Schema::create('units_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_name', 255);
            $table->string('document_path', 255);
            $table->unsignedBigInteger('document_size')->nullable();
            $table->string('document_extension', 10)->nullable();
            $table->text('delete_reason')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['unit_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units_documents');
        Schema::dropIfExists('offices_documents');
    }
};
