<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Horsefly\Setting;

trait Geocode
{
    public function geocode($address)
    {
        if (empty($address)) {
            $error = "Geocode called with empty address.";
            Log::warning($error);
            return ['error' => $error];
        }

        $address = urlencode($address) . ',UK';

        $setting = Setting::where('key', 'google_map_api')->first();
        $apiKey = $setting ? $setting->value : '';

        if (!$apiKey) {
            $error = "Google Maps API key is missing in config.";
            Log::error($error);
            return ['error' => $error];
        }

        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

        try {
            $response = file_get_contents($url);

            if (!$response) {
                $error = "Empty response from Google Maps API.";
                Log::error($error);
                return ['error' => $error];
            }

            $json = json_decode($response, true);

            if (!isset($json['status'])) {
                $error = "Malformed response from Google Maps API.";
                Log::error($error);
                return ['error' => $error];
            }

            switch ($json['status']) {
                case 'OK':
                    $lat = $json['results'][0]['geometry']['location']['lat'] ?? null;
                    $lng = $json['results'][0]['geometry']['location']['lng'] ?? null;

                    if ($lat && $lng) {
                        return ['lat' => $lat, 'lng' => $lng];
                    }

                    $error = "Geocode response missing coordinates.";
                    Log::warning($error);
                    return ['error' => $error];

                case 'ZERO_RESULTS':
                    $error = "No results found for address: {$address}";
                    Log::info($error);
                    return ['error' => $error];

                case 'OVER_QUERY_LIMIT':
                case 'REQUEST_DENIED':
                case 'INVALID_REQUEST':
                    $error = "Geocode API error: " . $json['status'] . " - " . ($json['error_message'] ?? 'No error message.');
                    Log::error($error);
                    return ['error' => $error];

                default:
                    $error = "Geocode unexpected status: " . $json['status'];
                    Log::error($error);
                    return ['error' => $error];
            }
        } catch (\Exception $e) {
            $error = "Geocode exception: " . $e->getMessage();
            Log::error($error);
            return ['error' => $error];
        }
    }

}
