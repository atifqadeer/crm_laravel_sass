<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per call the AI agent makes to an applicant about a job (sale).
 * Filled by POST /api/applicants/call-outcome. Nothing is overwritten except
 * when the agent re-sends the same call_id (e.g. to add the transcript later).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_agent_calls')) {
            return;
        }

        Schema::create('ai_agent_calls', function (Blueprint $table) {
            $table->id();
            // The agent's own id for the call: makes retries safe (no duplicates).
            $table->string('call_id', 100)->unique();

            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();

            // What happened on the line, and what the applicant said.
            $table->string('call_status', 30);          // answered | no_answer | busy | voicemail | wrong_number | failed
            $table->string('outcome', 30)->nullable();   // interested | not_interested | callback | not_looking | do_not_call
            // The one status the CRM shows and filters on: outcome when answered, else call_status.
            $table->string('status', 30);

            $table->dateTime('callback_at')->nullable();
            $table->dateTime('called_at');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('summary')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('recording_url', 2048)->nullable();
            // The full request as received, so no detail the agent sends is lost.
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['applicant_id', 'id']);
            $table->index(['sale_id', 'applicant_id', 'id']);
            $table->index(['status', 'id']);
            $table->index('called_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_agent_calls');
    }
};
