<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Horsefly\Applicant;
use Horsefly\JobTitle;
use Horsefly\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateAvailableJobStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-available-job-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update available job status for applicants based on their sales radius';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get applicants with required filters
        $result = Applicant::with(['cv_notes' => function ($query) {
            $query->select('status', 'applicant_id', 'sale_id', 'user_id')
                ->with(['user:id,name'])
                ->latest('cv_notes.created_at');
            }])
            ->select(
                'applicants.id',
                'applicants.job_title_id',
                'applicants.applicant_postcode',
                'applicants.lat',
                'applicants.lng',
                'applicants.is_job_within_radius',
            )
            ->where('applicants.status', 1)
            ->where('applicants.is_blocked', false)
            ->get();

        foreach ($result as $applicant) {        
            $data = [];
            // Sanitize fields and save status as before
            $data['job_title_id'] = mb_convert_encoding($applicant->job_title_id, 'UTF-8', 'UTF-8');
            $data['applicant_postcode'] = mb_convert_encoding($applicant->applicant_postcode, 'UTF-8', 'UTF-8');
            $data['is_job_within_radius'] = mb_convert_encoding($applicant->is_job_within_radius, 'UTF-8', 'UTF-8');
            $data['lat'] = mb_convert_encoding($applicant->lat, 'UTF-8', 'UTF-8');
            $data['lng'] = mb_convert_encoding($applicant->lng, 'UTF-8', 'UTF-8');
        
            $radius = 15; // kilometers
            
            // Perform the radius check and update the job status
            $isNearSales = $this->checkNearbySales($data, $radius);

            $applicant->is_job_within_radius = $isNearSales ? true : false;
        
            // Disable timestamps to prevent `updated_at` from being updated
            $applicant->timestamps = false;
        
            // Save the applicant without modifying `updated_at`
            $applicant->save();
        }

        $this->info('Applicant job statuses updated successfully.');
    }

    private function checkNearbySales($data, $radius)
    {
        $lat = $data->lat;
        $lon = $data->lng;

        $jobTitle = JobTitle::find($data->job_title_id);

        // Decode related_titles safely and normalize
        $relatedTitles = is_array($jobTitle->related_titles)
            ? $jobTitle->related_titles
            : json_decode($jobTitle->related_titles ?? '[]', true);

        // Make sure it's an array, lowercase all, and add main title
        $titles = collect($relatedTitles)
            ->map(fn($item) => strtolower(trim($item)))
            ->push(strtolower(trim($jobTitle->name)))
            ->unique()
            ->values()
            ->toArray();


        $jobTitleIds = JobTitle::whereIn(DB::raw('LOWER(name)'), $titles)->pluck('id')->toArray();

        $location_distance = Sale::selectRaw("
            *, 
            (ACOS(
                SIN(? * PI() / 180) * SIN(lat * PI() / 180) +
                COS(? * PI() / 180) * COS(lat * PI() / 180) *
                COS((? - lng) * PI() / 180)
            ) * 180 / PI()) * 111.045 AS distance,  -- distance in KM

            (SELECT COUNT(cv_notes.sale_id) 
            FROM cv_notes 
            WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1 
            GROUP BY cv_notes.sale_id) AS cv_notes_count
        ", [$lat, $lat, $lon])
            ->where("sales.status", 1)
            ->where("sales.is_on_hold", 0)
            ->havingRaw("distance < ?", [$radius]) // radius now in KM
            ->whereIn("sales.job_title_id", $jobTitleIds)
            ->get();

        return $location_distance->isNotEmpty();
    }
}
