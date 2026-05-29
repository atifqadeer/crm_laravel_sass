<?php

use App\Http\Controllers\CallLogController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authenticated user endpoint (Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── xplosip softphone integration ─────────────────────────────────────────
// These endpoints are consumed by the floating xplosip widget embedded in the
// CRM layout.  Authenticated via Sanctum token (see xplosip settings panel).
Route::middleware('auth:sanctum')->group(function () {

    // GET /api/contacts/lookup?number=07700123456
    // Returns matching applicant name + CRM URL for click-to-dial / caller ID.
    Route::get('/contacts/lookup', [CallLogController::class, 'lookup'])
         ->name('api.contacts.lookup');

    // POST /api/calls/log
    // Stores a completed call record from the xplosip widget.
    Route::post('/calls/log', [CallLogController::class, 'store'])
         ->name('api.calls.log');
});

// ── Desktop MicroSIP integration ───────────────────────────────────────────
// Token-secured (shared secret in the URL) because the desktop client cannot
// hold a Sanctum session. Set MicroSIP's crmApiUrl to:
//     https://your-crm/api/sip/<MICROSIP_API_TOKEN>
// The {token} is validated inside the controller via hash_equals.
Route::prefix('sip/{token}')->group(function () {

    // GET /api/sip/{token}/lookup?number=07700123456   (caller-ID lookup)
    Route::get('/lookup', [CallLogController::class, 'desktopLookup'])
         ->name('api.sip.lookup');

    // POST /api/sip/{token}/calls                       (call-log sink)
    Route::post('/calls', [CallLogController::class, 'desktopStore'])
         ->name('api.sip.calls');
});
