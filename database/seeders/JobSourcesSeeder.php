<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sources = [
            [
            'name' => 'Niche',
            'slug' => 'niche',
            'description' => 'Specialized job platform',
            'website' => null,
            ],
            [
            'name' => 'Reed',
            'slug' => 'reed',
            'description' => 'UK-based job search platform',
            'website' => 'https://www.reed.co.uk',
            ],
            [
            'name' => 'Total Job',
            'slug' => 'total-job',
            'description' => 'Comprehensive job search engine',
            'website' => 'https://www.totaljobs.com',
            ],
            [
            'name' => 'Referral',
            'slug' => 'referral',
            'description' => 'Employee or partner referrals',
            'website' => null,
            ],
            [
            'name' => 'CV Library',
            'slug' => 'cv-library',
            'description' => 'UK-based CV database and job board',
            'website' => 'https://www.cv-library.co.uk',
            ],
            [
            'name' => 'Social Media',
            'slug' => 'social-media',
            'description' => 'Job postings on social media platforms',
            'website' => null,
            ],
            [
            'name' => 'Other Source',
            'slug' => 'other-source',
            'description' => 'Miscellaneous or unclassified sources',
            'website' => null,
            ],
        ];

        foreach ($sources as $source) {
            DB::table('job_sources')->insert([
                'name' => $source['name'],
                'slug' => $source['slug'] ?? Str::slug($source['name']),
                'description' => $source['description'],
                'website' => $source['website'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}