<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applicant_id')->nullable()->constrained('applicants')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->string('caller_number', 50)->index();
            $table->string('caller_name', 255)->nullable();
            $table->enum('direction', ['inbound', 'outbound', 'missed'])->default('outbound')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('sip_call_id', 255)->nullable()->index();
            $table->timestamp('called_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
