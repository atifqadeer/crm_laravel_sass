<?php

namespace App\Http\Controllers;

use Horsefly\Applicant;
use Horsefly\CallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CallLogController extends Controller
{
    /**
     * GET /api/contacts/lookup?number=07700123456
     *
     * Returns the best-matching applicant (or contact) for a phone number.
     * Called by the xplosip widget on incoming/outgoing calls to show
     * the caller's name and a CRM link in the softphone panel.
     *
     * Response 200:
     *   { "found": true,  "name": "Jane Doe", "crmUrl": "https://crm/applicants/42", "id": 42 }
     *   { "found": false, "name": null, "crmUrl": null, "id": null }
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'number' => ['required', 'string', 'max:30'],
        ]);

        $raw = $request->input('number');

        // Search primary, secondary, and landline columns (indexed when the
        // normalized phone columns exist; REGEXP fallback otherwise).
        $applicant = Applicant::query()
            ->whereNull('deleted_at')
            ->phoneMatches($raw)
            ->select(['id', 'applicant_name'])
            ->first();

        if ($applicant) {
            return response()->json([
                'found'  => true,
                'name'   => $applicant->applicant_name,
                'crmUrl' => url('/applicants/' . $applicant->id),
                'id'     => $applicant->id,
            ]);
        }

        return response()->json([
            'found'  => false,
            'name'   => null,
            'crmUrl' => null,
            'id'     => null,
        ]);
    }

    /**
     * POST /api/calls/log
     *
     * Stores a completed call record sent by the xplosip widget.
     *
     * Expected JSON body:
     * {
     *   "number":      "07700123456",
     *   "name":        "Jane Doe",        // optional — caller name resolved by widget
     *   "direction":   "inbound",         // inbound | outbound | missed
     *   "duration":    65,                // seconds
     *   "callId":      "abc123@sip...",   // SIP Call-ID header value
     *   "calledAt":    "2026-05-27T14:30:00Z"  // ISO-8601 UTC
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number'    => ['required', 'string', 'max:30'],
            'name'      => ['nullable', 'string', 'max:255'],
            'direction' => ['required', 'in:inbound,outbound,missed'],
            'duration'  => ['nullable', 'integer', 'min:0'],
            'callId'    => ['nullable', 'string', 'max:255'],
            'calledAt'  => ['nullable', 'date'],
        ]);

        // Best-effort applicant link
        $applicant = Applicant::whereNull('deleted_at')
            ->phoneMatches($validated['number'])
            ->select('id')
            ->first();

        try {
            $log = CallLog::create([
                'user_id'          => Auth::id(),
                'applicant_id'     => $applicant?->id,
                'caller_number'    => $validated['number'],
                'caller_name'      => $validated['name'] ?? null,
                'direction'        => $validated['direction'],
                'duration_seconds' => $validated['duration'] ?? 0,
                'sip_call_id'      => $validated['callId'] ?? null,
                'called_at'        => isset($validated['calledAt'])
                                        ? \Carbon\Carbon::parse($validated['calledAt'])
                                        : now(),
            ]);

            return response()->json([
                'success' => true,
                'id'      => $log->id,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('CallLog store failed', [
                'error' => $e->getMessage(),
                'data'  => $validated,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store call log.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DESKTOP MicroSIP integration
    //
    //  The desktop MicroSIP client cannot present a Sanctum session, so these
    //  endpoints are protected by a shared secret token embedded in the URL:
    //      crmApiUrl = https://your-crm/api/sip/<MICROSIP_API_TOKEN>
    //  giving:
    //      GET  /api/sip/<token>/lookup?number=...
    //      POST /api/sip/<token>/calls
    //
    //  The request/response shapes match exactly what CRMIntegration.cpp
    //  sends/parses (keys: name, company, crmUrl, id / number, name, duration,
    //  callid, timestamp, direction with in|out|missed).
    // ════════════════════════════════════════════════════════════════════════

    /** Validate the shared secret or abort 403. */
    private function assertDesktopToken(?string $token): void
    {
        $expected = config('services.microsip.token');
        if (empty($expected) || !is_string($token) || !hash_equals($expected, $token)) {
            abort(403, 'Invalid MicroSIP API token');
        }
    }

    /**
     * GET /api/sip/{token}/lookup?number=07700123456
     * Caller-ID lookup for the desktop softphone (matches ParseLookupResponse).
     */
    public function desktopLookup(Request $request, string $token): JsonResponse
    {
        $this->assertDesktopToken($token);

        $request->validate(['number' => ['required', 'string', 'max:30']]);

        $raw = $request->input('number');

        $applicant = Applicant::query()
            ->whereNull('deleted_at')
            ->phoneMatches($raw)
            ->select(['id', 'applicant_name'])
            ->first();

        if ($applicant) {
            // Build the contact URL from APP_URL (not the request host) so the
            // desktop client always gets the correct public link to open.
            $base = rtrim(config('app.url'), '/');

            // NOTE: id returned as STRING — CRMIntegration::ParseLookupResponse
            // only reads id when it is a JSON string.
            return response()->json([
                'found'   => true,
                'name'    => $applicant->applicant_name,
                'company' => '',
                'crmUrl'  => $base . '/applicants/' . $applicant->id,
                'id'      => (string) $applicant->id,
            ]);
        }

        return response()->json([
            'found'   => false,
            'name'    => '',
            'company' => '',
            'crmUrl'  => '',
            'id'      => '',
        ]);
    }

    /**
     * POST /api/sip/{token}/calls
     * Call-log sink for the desktop softphone (matches LogCallEnd payload).
     *
     * Body: { number, name, duration, callid, timestamp, direction }
     *   direction: "in" | "out" | "missed"
     */
    public function desktopStore(Request $request, string $token): JsonResponse
    {
        $this->assertDesktopToken($token);

        $data = $request->validate([
            'number'    => ['required', 'string', 'max:30'],
            'name'      => ['nullable', 'string', 'max:255'],
            'duration'  => ['nullable', 'integer', 'min:0'],
            'callid'    => ['nullable', 'string', 'max:255'],
            'timestamp' => ['nullable', 'string', 'max:40'],
            'direction' => ['nullable', 'string', 'max:10'],
        ]);

        // Map MicroSIP direction (in/out/missed) → CRM enum (inbound/outbound/missed)
        $dirMap = ['in' => 'inbound', 'out' => 'outbound', 'missed' => 'missed'];
        $direction = $dirMap[strtolower($data['direction'] ?? 'out')] ?? 'outbound';

        // Parse timestamp safely; fall back to now()
        $calledAt = now();
        if (!empty($data['timestamp'])) {
            try { $calledAt = \Carbon\Carbon::parse($data['timestamp']); } catch (\Throwable $e) { /* keep now() */ }
        }

        // Best-effort applicant link by phone match
        $applicant = Applicant::whereNull('deleted_at')
            ->phoneMatches($data['number'])
            ->select('id')
            ->first();

        try {
            $log = CallLog::create([
                'user_id'          => null, // desktop client is not session-bound
                'applicant_id'     => $applicant?->id,
                'caller_number'    => $data['number'],
                'caller_name'      => $data['name'] ?? null,
                'direction'        => $direction,
                'duration_seconds' => $data['duration'] ?? 0,
                'sip_call_id'      => $data['callid'] ?? null,
                'source'           => 'desktop',
                'called_at'        => $calledAt,
            ]);

            // Free the dial lock now the call ended, but KEEP the row so the
            // per-number call counter is preserved.
            $lockKey = \Horsefly\DialLock::keyFor($data['number']);
            if ($lockKey) {
                \Horsefly\DialLock::where('phone_key', $lockKey)
                    ->update(['expires_at' => now()->subSecond()]);
            }

            return response()->json(['success' => true, 'id' => $log->id], 201);
        } catch (\Throwable $e) {
            Log::error('Desktop CallLog store failed', ['error' => $e->getMessage(), 'data' => $data]);
            return response()->json(['success' => false, 'message' => 'Failed to store call log.'], 500);
        }
    }
}
