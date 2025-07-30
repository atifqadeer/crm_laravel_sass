<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaleDocumentsTable extends Migration
{
    public function up()
    {
        Schema::create('sale_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_id');
            $table->string('document_name', 255);
            $table->string('document_path', 255);
            $table->string('document_size', 10)->nullable()->default(null);
            $table->string('document_extension', 10)->nullable()->default(null);
            
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign key constraints
            $table->foreign('sale_id')->references('id')->on('sales');

            // Optional indexes for better performance
            $table->index('sale_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sale_documents');
    }
}

