<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateApplicantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('applicant_uid', 255)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('job_source_id')->nullable();
            $table->unsignedBigInteger('job_category_id')->nullable();
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->string('job_type')->nullable();
            $table->string('applicant_name', 255);
            $table->string('applicant_email', 255);
            $table->string('applicant_postcode', 50)->nullable();
            $table->string('applicant_phone', 50);
            $table->string('applicant_landline', 50)->nullable();
            $table->string('applicant_cv', 255)->nullable();
            $table->string('updated_cv', 255)->nullable();
            $table->string('applicant_notes', 255)->nullable();
            $table->string('applicant_experience', 255)->nullable();
            $table->enum('gender', ['m', 'f', 'u'])->default('u');
            $table->float('lat', 15, 6)->nullable();
            $table->float('lng', 15, 6)->nullable();

            // Boolean flags
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_temp_not_interested')->default(false);
            $table->boolean('is_callback_enable')->default(false);
            $table->boolean('is_no_job')->default(false);
            $table->boolean('is_no_response')->default(false);
            $table->boolean('is_in_nurse_home')->default(false);
            $table->boolean('is_circuit_busy')->default(false);
            $table->boolean('is_cv_in_quality')->default(false);
            $table->boolean('is_cv_in_quality_clear')->default(false);
            $table->boolean('is_cv_sent')->default(false);
            $table->boolean('is_cv_reject')->default(false);
            $table->boolean('is_interview_confirm')->default(false);
            $table->boolean('is_interview_attend')->default(false);
            $table->boolean('is_in_crm_request')->default(false);
            $table->boolean('is_in_crm_reject')->default(false);
            $table->boolean('is_in_crm_request_reject')->default(false);
            $table->boolean('is_crm_request_confirm')->default(false);
            $table->boolean('is_crm_interview_attended')->default(false)->comment('0=not,1=yes,2=pending');
            $table->boolean('is_in_crm_start_date')->default(false);
            $table->boolean('is_in_crm_invoice')->default(false);
            $table->boolean('is_in_crm_invoice_sent')->default(false);
            $table->boolean('is_in_crm_start_date_hold')->default(false);
            $table->boolean('is_in_crm_paid')->default(false);
            $table->boolean('is_in_crm_dispute')->default(false);
            $table->boolean('is_job_within_radius')->default(true);
            $table->tinyInteger('have_nursing_home_experience')->default(null);

            $table->tinyInteger('status')->default(1);
            $table->string('paid_status', 20)->default('pending');
            $table->timestamp('paid_timestamp')->nullable();

            $table->softDeletes();

            // Timestamps with default values and automatic update on change
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('job_source_id')->references('id')->on('job_sources')->onDelete('set null');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('set null');
            $table->foreign('job_category_id')->references('id')->on('job_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applicants');
    }
}
