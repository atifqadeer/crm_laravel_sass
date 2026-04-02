<?php

namespace App\Services;

use Horsefly\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapService
{
    /**
     * Run a specific actor by its DB key e.g. scrap_apify_indeed
     */
    public function runByKey(string $key, array $input = []): array
    {
        $settings = Setting::where('key', $key)
                           ->where('group', 'scraper')
                           ->first();

        if (! $settings) {
            throw new \RuntimeException("Scraper actor [{$key}] not found in settings.");
        }

        $actor = json_decode($settings->value, true);

        if (empty($actor) || ! is_array($actor)) {
            throw new \RuntimeException("Invalid actor configuration for key [{$key}].");
        }

        return $this->runConfig($actor, $input);
    }

    /**
     * Run all scraper actors stored in DB
     */
    public function runAll(array $input = []): array
    {
        $allSettings = Setting::where('group', 'scraper')
                              ->where('type', 'json')
                              ->where('key', 'like', 'scrap_%')
                              ->get();

        $results = [];

        foreach ($allSettings as $setting) {
            $actor = json_decode($setting->value, true);

            if (empty($actor['base_url'])) {
                Log::warning("[Scraper] Skipping [{$setting->key}] — missing base_url.");
                continue;
            }

            try {
                $results[$setting->key] = $this->runConfig($actor, $input);
                Log::info("[Scraper] [{$setting->key}] completed.");
            } catch (\Throwable $e) {
                Log::error("[Scraper] [{$setting->key}] failed.", ['error' => $e->getMessage()]);
                $results[$setting->key] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Core runner — accepts a plain actor array
     */
    public function runConfig(array $actor, array $input = []): array
    {
        $token    = trim($actor['token']    ?? '');
        $baseUrl  = trim($actor['base_url'] ?? '') ?: config('services.scrap.base_url', 'https://api.apify.com/v2');
        $actorId  = trim($actor['actor_id'] ?? '');

        if (empty($baseUrl)) {
            throw new \RuntimeException('Missing base URL in actor configuration.');
        }

        // Extract token from URL query string if not set explicitly
        $parsedUrl   = parse_url($baseUrl);
        $queryParams = [];
        if (! empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        if (empty($token) && ! empty($queryParams['token'])) {
            $token = trim($queryParams['token']);
        }

        $baseUrl  = rtrim($baseUrl, '/');
        $pathOnly = explode('?', $baseUrl, 2)[0];
        $urlPath  = rtrim($pathOnly, '/');

        // Build HTTP client
        $request = Http::acceptJson()->timeout(120)->retry(3, 5000);
        if (! empty($token)) {
            $request = $request->withToken($token);
        }

        // ---------------------------------------------------------------
        // DATASET endpoint → GET /items
        //   Matches ANY of:
        //   • base_url contains  /datasets/{id}  (e.g. .../datasets/ABC123/items)
        //   • base_url ends with /datasets       (e.g. .../datasets)
        //   • base_url ends with /datasets/      (e.g. .../datasets/)
        // ---------------------------------------------------------------
        if (str_contains($urlPath, '/datasets/') || preg_match('#/datasets/?$#', $urlPath)) {

            // URL ends with /datasets — actor_id IS the dataset ID
            if (preg_match('#/datasets/?$#', $urlPath)) {
                if (empty($actorId)) {
                    throw new \RuntimeException('Dataset ID (actor_id) is required when base_url ends with /datasets.');
                }
                $endpoint = rtrim($urlPath, '/') . "/{$actorId}/items";

            // URL already contains /datasets/{id}/... — just ensure /items suffix
            } else {
                $endpoint = $urlPath;
                if (! str_ends_with($endpoint, '/items')) {
                    $endpoint .= '/items';
                }
            }

            if (empty($queryParams['token']) && ! empty($token)) {
                $queryParams['token'] = $token;
            }
            if (! empty($queryParams)) {
                $endpoint .= '?' . http_build_query($queryParams);
            }

            $response = $request->get($endpoint);

        // ---------------------------------------------------------------
        // ACTOR endpoint → POST /runs
        // ---------------------------------------------------------------
        } else {
            if (empty($actorId)) {
                throw new \RuntimeException('Missing actor_id in configuration.');
            }

            if (str_contains($urlPath, '/acts/') && str_contains($urlPath, '/runs')) {
                $endpoint = $urlPath;
            } elseif (str_contains($urlPath, '/acts/')) {
                $endpoint = rtrim($urlPath, '/');
                if (! str_ends_with($endpoint, '/runs')) {
                    $endpoint .= '/runs';
                }
            } elseif (preg_match('#/acts$#', $urlPath)) {
                $endpoint = "{$urlPath}/{$actorId}/runs";
            } else {
                $endpoint = "{$urlPath}/acts/{$actorId}/runs";
            }

            if (! empty($queryParams)) {
                $endpoint .= '?' . http_build_query($queryParams);
            }

            $response = $request->post($endpoint, ['input' => $input]);
        }

        if ($response->failed()) {
            Log::error('[Scraper] Request failed', [
                'actor_id' => $actorId,
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            throw new \RuntimeException(
                'Scraper request failed: ' . json_encode($response->json() ?: $response->body())
            );
        }

        return $response->json();
    }
}