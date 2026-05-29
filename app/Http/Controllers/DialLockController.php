<?php

namespace App\Http\Controllers;

use Horsefly\Applicant;
use Horsefly\DialLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Prevents the same number from being dialled again while it is "in use".
 *
 * A number becomes locked the moment any agent dials it, and stays locked for
 * DIAL_LOCK_MINUTES (or until the desktop softphone reports the call ended).
 * While locked, NO agent — not even the one who started it — can re-dial it;
 * they get a message with who is on it, how long ago, the remaining time, and
 * the total number of times that number has been called.
 *
 *   info()    GET  — read current status (count / timing / locked) for a number
 *   acquire() POST — try to start a call (locks + increments the counter)
 *   release() POST — manually release a lock the agent holds
 */
class DialLockController extends Controller
{
    /** How long a number stays locked after being dialled (minutes). */
    private function lockMinutes(): int
    {
        return max(1, (int) (config('services.dialing.lock_minutes') ?: 5));
    }

    /** GET /dialing/info?number=... — status used to pre-fill the dialog. */
    public function info(Request $request): JsonResponse
    {
        $request->validate(['number' => ['required', 'string', 'max:30']]);
        $key = DialLock::keyFor($request->input('number'));

        if (!$key) {
            return response()->json(['callCount' => 0, 'locked' => false, 'lastCalledAgo' => null]);
        }

        $row = DialLock::where('phone_key', $key)->first();
        if (!$row) {
            return response()->json(['callCount' => 0, 'locked' => false, 'lastCalledAgo' => null]);
        }

        $locked    = $row->expires_at && $row->expires_at->isFuture();
        return response()->json([
            'callCount'        => (int) $row->call_count,
            'lastCalledAgo'    => $row->locked_at ? $row->locked_at->diffForHumans() : null,
            'lastCalledBy'     => $row->user_name,
            'locked'           => $locked,
            'lockedBy'         => $locked ? $row->user_name : null,
            'remainingSeconds' => $locked ? (int) ceil(now()->diffInSeconds($row->expires_at)) : 0,
        ]);
    }

    /** POST /dialing/acquire — start a call if the number is free. */
    public function acquire(Request $request): JsonResponse
    {
        $request->validate(['number' => ['required', 'string', 'max:30']]);

        $number = trim($request->input('number'));
        $key    = DialLock::keyFor($number);

        // Numbers too short to lock (internal extensions) are always allowed.
        if (!$key) {
            return response()->json(['ok' => true, 'locked' => false, 'callCount' => 0]);
        }

        $user  = Auth::user();
        $now   = now();
        $until = $now->copy()->addMinutes($this->lockMinutes());

        // Best-effort applicant link (indexed when the normalized columns exist).
        $applicantId = Applicant::whereNull('deleted_at')
            ->phoneMatches($number)
            ->value('id');

        // Serialise so two simultaneous clicks can't both win.
        return DB::transaction(function () use ($key, $number, $user, $now, $until, $applicantId) {
            $row = DialLock::where('phone_key', $key)->lockForUpdate()->first();

            // ── Currently locked → block EVERYONE (incl. same agent) ──────────
            if ($row && $row->expires_at && $row->expires_at->isFuture()) {
                $remaining = (int) ceil($now->diffInSeconds($row->expires_at));
                $ago       = $row->locked_at ? $row->locked_at->diffForHumans() : 'just now';
                $who       = $row->user_name ?: 'another agent';

                return response()->json([
                    'ok'               => false,
                    'locked'           => true,
                    'lockedBy'         => $who,
                    'callCount'        => (int) $row->call_count,
                    'remainingSeconds' => $remaining,
                    'lastCalledAgo'    => $ago,
                    'message'          => "This number is being called by {$who} ({$ago}). "
                                        . "Locked for another " . $this->humanSeconds($remaining) . ". "
                                        . "Called " . (int) $row->call_count . "× in total.",
                ], 423);
            }

            // ── Free (or expired) → allow, lock it, and bump the counter ──────
            $newCount = ($row ? (int) $row->call_count : 0) + 1;

            DialLock::updateOrCreate(
                ['phone_key' => $key],
                [
                    'full_number'  => $number,
                    'user_id'      => $user->id,
                    'user_name'    => $user->name,
                    'applicant_id' => $applicantId,
                    'call_count'   => $newCount,
                    'locked_at'    => $now,
                    'expires_at'   => $until,
                ]
            );

            return response()->json([
                'ok'        => true,
                'locked'    => false,
                'callCount' => $newCount,
            ]);
        });
    }

    /** POST /dialing/release — free a lock the agent holds. */
    public function release(Request $request): JsonResponse
    {
        $request->validate(['number' => ['required', 'string', 'max:30']]);

        $key = DialLock::keyFor($request->input('number'));
        if ($key) {
            // Free the active lock but KEEP the row so the counter survives.
            DialLock::where('phone_key', $key)
                ->where('user_id', Auth::id())
                ->update(['expires_at' => now()->subSecond()]);
        }

        return response()->json(['ok' => true]);
    }

    /** Human-friendly seconds → "2m 5s" / "45s". */
    private function humanSeconds(int $s): string
    {
        if ($s >= 60) {
            $m = intdiv($s, 60);
            $r = $s % 60;
            return $r ? "{$m}m {$r}s" : "{$m}m";
        }
        return "{$s}s";
    }
}
