<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Horsefly\User;
use Horsefly\JobCategory;
use Horsefly\Sale; 
use Horsefly\Office; 
use Horsefly\Unit; 
use Horsefly\Contact; 
use Horsefly\Applicant; 
use Horsefly\ApplicantNote;
use Horsefly\JobTitle;
use Horsefly\SaleNote;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        // Users with Offices, Units, Contacts, Applicants, ApplicantNotes
        User::factory()
            ->count(10)
            ->has(
                Office::factory()
                    ->count(2)
                    ->has(
                        Unit::factory()
                            ->count(3)
                            ->has(Contact::factory()->count(2), 'contacts'),
                        'units'
                    ),
                'offices'
            )
            ->has(
                Applicant::factory()
                    ->count(5)
                    ->has(ApplicantNote::factory()->count(3), 'applicant_notes'),
                'applicants'
            )
            ->create();

        // Job Categories with Job Titles
        JobCategory::factory()
            ->count(5)
            ->has(JobTitle::factory()->count(3), 'job_titles')
            ->create();

        // Sales with Sale Notes
        Sale::factory()
            ->count(20)
            ->has(SaleNote::factory()->count(2), 'sale_notes')
            ->create();
    }
}