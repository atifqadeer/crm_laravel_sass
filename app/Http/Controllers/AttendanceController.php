<?php

namespace App\Http\Controllers;

use App\Services\AttendanceTracker;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Sent by the layout every few minutes while the user is active in the portal (footer-scripts).
     * When a user never logs out, the last of these is their check-out time.
     */
    public function heartbeat(Request $request, AttendanceTracker $tracker)
    {
        $tracker->touch($request->user(), (string) $request->ip());

        return response()->noContent();
    }
}
