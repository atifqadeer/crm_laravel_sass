<?php

namespace App\Support;

use Horsefly\Setting;
use Illuminate\Http\Request;

/**
 * API keys stored in the settings table, one row per integration, so each
 * can be changed or revoked on its own without a deploy:
 *
 *   api_key_active_sales   open sales feed, sale details, regions (ApiController)
 *   api_key_ai_automation  applicant job matches + applicant update (ApplicantMatchApiController)
 *
 * Callers send the key as: Authorization: Bearer <key>, X-API-Token: <key>, or api_token=<key>.
 */
class SettingApiKey
{
    public const ACTIVE_SALES = 'api_key_active_sales';
    public const AI_AUTOMATION = 'api_key_ai_automation';

    public static function value(string $settingKey): ?string
    {
        $value = Setting::where('key', $settingKey)->value('value');
        $value = is_string($value) ? trim($value) : '';

        return $value === '' ? null : $value;
    }

    public static function matches(string $settingKey, ?string $provided): bool
    {
        $expected = self::value($settingKey);

        // A missing or blank key refuses everything; it never falls open.
        return $expected !== null
            && is_string($provided)
            && $provided !== ''
            && hash_equals($expected, $provided);
    }

    /** Abort with a JSON 401 unless the request carries the key stored under $settingKey. */
    public static function assert(Request $request, string $settingKey): void
    {
        $provided = $request->bearerToken()
            ?: $request->header('X-API-Token')
            ?: $request->input('api_token');

        if (!self::matches($settingKey, is_string($provided) ? $provided : null)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing API token.',
            ], 401));
        }
    }
}
