<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Horsefly\Applicant;
use Horsefly\JobTitle;
use Horsefly\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Event;
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
        $result = Applicant::select(
                'applicants.id',
                'applicants.job_title_id',
                'applicants.applicant_postcode',
                'applicants.lat as app_latitude',
                'applicants.lng as app_longitude',
                'applicants.is_job_within_radius',
            )
            ->where('applicants.status', 1)
            ->where('applicants.is_blocked', false)
            ->get()->take(50);

        foreach ($result as $applicant) {        
            $data = [
                'job_title_id'        => $applicant->job_title_id,
                'applicant_postcode'  => $applicant->applicant_postcode,
                'is_job_within_radius'=> $applicant->is_job_within_radius,
                'lat'                 => $applicant->app_latitude,
                'lng'                 => $applicant->app_longitude,
            ];

            $radius = 15; // kilometers

            $isNearSales = $this->checkNearbySales($data, $radius);

            $applicant->is_job_within_radius = $isNearSales;

            // Save without triggering audits and without updating timestamps
            Event::withoutEvents(function () use ($applicant, $isNearSales) {
                $applicant->timestamps = false;
                $applicant->is_job_within_radius = $isNearSales;
                $applicant->save();
            });
        }

        $this->info('Applicant job statuses updated successfully.');
    }

    private function checkNearbySales(array $data, int $radius)
    {
        $lat = $data['lat'];
        $lon = $data['lng'];

        $jobTitle = JobTitle::find($data['job_title_id']);

        if (!$jobTitle) {
            return false;
        }

        $relatedTitles = is_array($jobTitle->related_titles)
            ? $jobTitle->related_titles
            : json_decode($jobTitle->related_titles ?? '[]', true);

        $titles = collect($relatedTitles)
            ->map(fn($item) => strtolower(trim($item)))
            ->push(strtolower(trim($jobTitle->name)))
            ->unique()
            ->values()
            ->toArray();

        $jobTitleIds = JobTitle::whereIn(DB::raw('LOWER(name)'), $titles)
            ->pluck('id')
            ->toArray();

        $location_distance = Sale::selectRaw("
                *,
                (ACOS(
                    SIN(? * PI() / 180) * SIN(lat * PI() / 180) +
                    COS(? * PI() / 180) * COS(lat * PI() / 180) *
                    COS((? - lng) * PI() / 180)
                ) * 180 / PI()) * 111.045 AS distance
            ", [$lat, $lat, $lon])
            ->where("sales.status", 1)
            ->where("sales.is_on_hold", 0)
            ->whereIn("sales.job_title_id", $jobTitleIds)
            ->havingRaw("distance < ?", [$radius])
            ->get();

        return $location_distance->isNotEmpty();
    }

}
