<?php

namespace Database\Factories\Horsefly;

use Horsefly\Applicant;
use Horsefly\User;
use Horsefly\JobSource;
use Horsefly\JobCategory;
use Horsefly\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicantFactory extends Factory
{
    protected $model = Applicant::class;

    public function definition()
    {
        return [
            'applicant_uid' => $this->faker->uuid(),
            'user_id' => User::factory(),
            'job_source_id' => JobSource::factory(),
            'job_category_id' => JobCategory::factory(),
            'job_title_id' => JobTitle::factory(),
            'job_type' => $this->faker->randomElement(['full-time','part-time','contract']),
            'applicant_name' => $this->faker->name(),
            'applicant_email' => $this->faker->unique()->safeEmail(),
            'applicant_email_secondary' => $this->faker->optional()->safeEmail(),
            'date' => $this->faker->date(),
            'applicant_postcode' => strtoupper($this->faker->bothify('??## #??')),
            'applicant_phone' => $this->faker->phoneNumber(),
            'applicant_phone_secondary' => $this->faker->optional()->phoneNumber(),
            'applicant_landline' => $this->faker->optional()->phoneNumber(),
            'applicant_cv' => $this->faker->optional()->text(200),
            'updated_cv' => $this->faker->optional()->text(200),
            'applicant_notes' => $this->faker->paragraph(),
            'applicant_experience' => $this->faker->optional()->paragraph(),
            'lat' => $this->faker->latitude(),
            'lng' => $this->faker->longitude(),

            // Boolean flags
            'is_blocked' => $this->faker->boolean(10),
            'is_temp_not_interested' => $this->faker->boolean(10),
            'is_callback_enable' => $this->faker->boolean(20),
            'is_no_job' => $this->faker->boolean(10),
            'is_no_response' => $this->faker->boolean(15),
            'is_in_nurse_home' => $this->faker->boolean(5),
            'is_circuit_busy' => $this->faker->boolean(5),
            'is_cv_in_quality' => $this->faker->boolean(30),
            'is_cv_in_quality_clear' => $this->faker->boolean(20),
            'is_cv_sent' => $this->faker->boolean(50),
            'is_cv_in_quality_reject' => $this->faker->boolean(10),
            'is_interview_confirm' => $this->faker->boolean(25),
            'is_interview_attend' => $this->faker->boolean(20),
            'is_in_crm_request' => $this->faker->boolean(30),
            'is_in_crm_reject' => $this->faker->boolean(10),
            'is_in_crm_request_reject' => $this->faker->boolean(5),
            'is_crm_request_confirm' => $this->faker->boolean(15),
            'is_crm_interview_attended' => $this->faker->boolean(15),
            'is_in_crm_start_date' => $this->faker->boolean(10),
            'is_in_crm_invoice' => $this->faker->boolean(10),
            'is_in_crm_invoice_sent' => $this->faker->boolean(10),
            'is_in_crm_start_date_hold' => $this->faker->boolean(5),
            'is_in_crm_paid' => $this->faker->boolean(5),
            'is_in_crm_dispute' => $this->faker->boolean(3),
            'is_job_within_radius' => $this->faker->boolean(95),
            'have_nursing_home_experience' => $this->faker->optional()->numberBetween(0, 5),

            // Status & Payment
            'status' => $this->faker->randomElement([0,1,2]),
            'paid_status' => $this->faker->randomElement(['pending','paid','failed']),
            'paid_timestamp' => $this->faker->optional()->dateTime(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
