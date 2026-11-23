<?php

use App\Http\Controllers\CallLogController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\ApplicantMatchApiController;
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

// ── External portal: open / active sales feed ─────────────────────────────
// Auth: Authorization: Bearer <api_key_active_sales setting>  (or X-API-Token / ?api_token=)
// Same membership rules as SaleController::getOpenSales() (status=1, not on hold).
Route::get('/sales/open', [ApiController::class, 'openSalesApi'])
     ->name('api.sales.open');

Route::get('/sales/open/kingsburypersonnel', [ApiController::class, 'openkingsburypersonnelSalesApi'])
     ->name('api.sales.open');

Route::get('/sales/details/{id}', [ApiController::class, 'getSaleDetailsApi'])
     ->name('api.sales.details');

Route::get('/regions/active', [ApiController::class, 'activeRegionsApi'])
     ->name('api.regions.active');

// ── Applicant ⇄ job APIs ─────────────────────────────────────────────────
// Auth: settings key "api_key_ai_automation" (Authorization: Bearer / X-API-Token / api_token).
// Separate from the open-sales feed key above, so each can be changed or revoked on its own.
Route::middleware('throttle:60,1')->group(function () {
     // 1. Send data: applicants for the sales + radius published from the CRM page.
     //    GET /api/applicants/job-matches?page=1&per_page=100   (no sale ids needed)
     Route::get('/applicants/job-matches', [ApplicantMatchApiController::class, 'jobMatches'])
          ->name('api.applicants.jobMatches');

     // 2. Receive updates: change applicant records by applicant_id.
     //    POST /api/applicants/update  {"applicant_id":1,"phone":"07..."} or {"applicants":[...]}
     Route::post('/applicants/update', [ApplicantMatchApiController::class, 'updateApplicants'])
          ->name('api.applicants.update');
});

// 3. Call outcomes: the AI agent reports every call (stored in ai_agent_calls).
//    POST /api/applicants/call-outcome  one call, or {"calls":[...]}
//    Higher limit: the agent may report many calls at once.
Route::post('/applicants/call-outcome', [ApplicantMatchApiController::class, 'callOutcome'])
     ->middleware('throttle:300,1')
     ->name('api.applicants.callOutcome');


// ── xplosip softphone integration ─────────────────────────────────────────
// These endpoints are consumed by the floating xplosip widget embedded in the
// CRM layout. Support both Sanctum (API token) and web session authentication.
Route::middleware(['auth:sanctum,web'])->group(function () {

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
