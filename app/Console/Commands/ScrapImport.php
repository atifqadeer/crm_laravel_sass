<?php

namespace App\Console\Commands;

use App\Http\Controllers\ScrapController;
use App\Services\ScrapService;
use Horsefly\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapImport extends Command
{
    protected $signature = 'scrapper:import
                            {key? : Specific DB key e.g. scrap_apify_indeed}
                            {--input= : Optional JSON input payload}
                            {--all : Run all saved scraper actors}
                            {--fetch-only : Fetch & print jobs without saving to DB}';

    protected $description = 'Fetch jobs from scraper actor(s) and import them into the database.';

    public function handle(): int
    {
        $input = $this->resolveInput();

        if ($input === false) {
            return 1;
        }

        $service = new ScrapService();
        $controller = new ScrapController();
        $user = User::first();

        if (!$user) {
            $this->error('No users exist in the database. Cannot import.');
            return 1;
        }

        $fetchOnly = $this->option('fetch-only');

        // ---------------------------------------------------------------
        // --all flag: loop every scrap_* actor in DB
        // ---------------------------------------------------------------
        if ($this->option('all')) {
            $this->info('Running all scraper actors...');

            $results = $service->runAll($input);

            foreach ($results as $key => $result) {
                if (isset($result['error'])) {
                    $this->error("  [{$key}] FETCH FAILED: {$result['error']}");
                    continue;
                }

                $jobs = is_array($result) ? $result : [];
                $this->info("  [{$key}] Fetched " . count($jobs) . " job(s).");

                if ($fetchOnly) {
                    $this->line(json_encode($jobs, JSON_PRETTY_PRINT));
                    continue;
                }

                try {
                    $imported = $this->persistByKey($controller, $key, $jobs, $user);
                    $this->info("  [{$key}] Imported {$imported} new job(s) into DB.");

                    Log::info('[Scraper] CLI --all import completed', [
                        'key' => $key,
                        'fetched' => count($jobs),
                        'imported' => $imported,
                    ]);
                } catch (\Throwable $e) {
                    $this->error("  [{$key}] IMPORT FAILED: {$e->getMessage()}");
                    Log::error('[Scraper] CLI --all import failed', ['key' => $key, 'error' => $e->getMessage()]);
                }
            }

            return 0;
        }

        // ---------------------------------------------------------------
        // Specific key: e.g. scrap_apify_indeed
        // ---------------------------------------------------------------
        $key = $this->argument('key');

        if (empty($key)) {
            $this->error('Provide a key argument or use --all flag.');
            $this->line('  Usage: php artisan scrapper:import scrap_apify_indeed');
            $this->line('         php artisan scrapper:import scrap_apify_totaljob');
            $this->line('         php artisan scrapper:import --all');
            return 1;
        }

        try {
            $this->info("Fetching jobs from scraper actor: [{$key}]");
            $jobs = $service->runByKey($key, $input);

            $count = is_array($jobs) ? count($jobs) : 0;
            $this->info("Fetched {$count} job(s).");

            if ($fetchOnly) {
                $this->line(json_encode($jobs, JSON_PRETTY_PRINT));
                return 0;
            }

            if ($count === 0) {
                $this->warn('No jobs returned from the scraper API. Nothing imported.');
                return 0;
            }

            $this->info("Importing jobs into database...");
            $imported = $this->persistByKey($controller, $key, $jobs, $user);
            $this->info("Done. Imported {$imported} new job(s) (skipped duplicates).");

            Log::info('[Scraper] CLI import completed', [
                'key' => $key,
                'fetched' => $count,
                'imported' => $imported,
            ]);

            return 0;

        } catch (\Throwable $e) {
            $this->error("Failed: {$e->getMessage()}");
            Log::error('[Scraper] CLI import failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    // ---------------------------------------------------------------
    // Route to the correct persist method based on actor key
    // ---------------------------------------------------------------
    private function persistByKey(ScrapController $controller, string $key, array $jobs, $user): int
    {
        return match (true) {
            str_contains($key, 'scrap_apify_indeed') => $controller->persistJobsIndeed($jobs, $user),
            str_contains($key, 'scrap_apify_totaljob') => $controller->persistJobsTotalJob($jobs, $user),
            default => throw new \InvalidArgumentException("No persist handler defined for actor key: [{$key}]"),
        };
    }
    private function resolveInput(): array|false
    {
        $option = $this->option('input');

        if (empty($option)) {
            return [];
        }

        $decoded = json_decode($option, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON provided for --input: ' . json_last_error_msg());
            return false;
        }

        return $decoded;
    }
}