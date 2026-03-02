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
use Horsefly\JobSource;
use Horsefly\CrmNote;
use Horsefly\History;
use Horsefly\CVNote;
use Horsefly\QualityNotes;

class FactorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed base users
        User::factory()->count(10)->create();

        // 2. Seed job categories and titles
        JobCategory::factory()
            ->count(5)
            ->has(JobTitle::factory()->count(3), 'jobTitles')
            ->create();
        
        // 3. Seed job sources
        JobSource::factory()->count(3)->create();

        // 4. Seed with nested relationships
        User::factory()
            ->count(5)
            ->has(
                Office::factory()
                    ->count(1)
                    ->has(
                        Unit::factory()
                            ->count(2)
                            ->has(Contact::factory()->count(1), 'contacts'),
                        'units'
                    ),
                'offices'
            )
            ->has(
                Applicant::factory()
                    ->count(3)
                    ->has(ApplicantNote::factory()->count(2), 'applicant_notes'),
                'applicants'
            )
            ->create();

        // 5. Seed separately defined sales
        Sale::factory()
            ->count(10)
            ->has(SaleNote::factory()->count(1), 'saleNotes')
            ->create();

        // 6. Seed CRM Notes using each one picking a random existing ID
        for ($i = 0; $i < 20; $i++) {
            $user = User::inRandomOrder()->first();
            $applicant = Applicant::inRandomOrder()->first();
            $sale = Sale::inRandomOrder()->first();

            if ($user && $applicant && $sale) {
                CrmNote::factory()->create([
                    'user_id' => $user->id,
                    'applicant_id' => $applicant->id,
                    'sale_id' => $sale->id,
                ]);
            }
        }

        // 7. Seed History
        for ($i = 0; $i < 15; $i++) {
            $user = User::inRandomOrder()->first();
            $applicant = Applicant::inRandomOrder()->first();
            if ($user && $applicant) {
                History::factory()->create([
                    'user_id' => $user->id,
                    'applicant_id' => $applicant->id,
                    'sale_id' => Sale::inRandomOrder()->first()?->id,
                ]);
            }
        }

        // 8. Seed CV Notes
        for ($i = 0; $i < 15; $i++) {
            $user = User::inRandomOrder()->first();
            $applicant = Applicant::inRandomOrder()->first();
            if ($user && $applicant) {
                CVNote::factory()->create([
                    'user_id' => $user->id,
                    'applicant_id' => $applicant->id,
                    'sale_id' => Sale::inRandomOrder()->first()?->id,
                ]);
            }
        }

        // 9. Seed Quality Notes
        for ($i = 0; $i < 15; $i++) {
            $user = User::inRandomOrder()->first();
            $applicant = Applicant::inRandomOrder()->first();
            if ($user && $applicant) {
                QualityNotes::factory()->create([
                    'user_id' => $user->id,
                    'applicant_id' => $applicant->id,
                    'sale_id' => Sale::inRandomOrder()->first()?->id,
                ]);
            }
        }
    }
}