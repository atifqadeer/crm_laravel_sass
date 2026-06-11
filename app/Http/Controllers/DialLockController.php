<?php

namespace App\Http\Controllers;

use Horsefly\Applicant;
use Horsefly\DialLock;
use Horsefly\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Prevents the same number from being dialled again while it is "in use".
 *
 * Two independent lock timers are now supported (both configurable in Settings):
 *   same_user_minutes   — how long the dialling agent themselves is re-locked
 *                         (0 = same agent can re-dial immediately)
 *   other_user_minutes  — how long ALL other agents are blocked (default 5 min)
 *
 * The master "dialing_lock_enabled" toggle bypasses all locking when off.
 */
class DialLockController extends Controller
{
    private function dialingSettings(): array
    {
        $rows = Setting::where('group', 'dialing')->pluck('value', 'key');
        return [
            'enabled'            => filter_var($rows->get('dialing_lock_enabled', 'true'), FILTER_VALIDATE_BOOLEAN),
            'same_user_minutes'  => max(0, (int) $rows->get('dialing_lock_same_user_minutes', 0)),
            'other_user_minutes' => max(1, (int) $rows->get('dialing_lock_other_user_minutes', 5)),
        ];
    }

    /** GET /dialing/info?number=... — status for a number, personalised to the calling agent. */
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

        $cfg    = $this->dialingSettings();
        $isSelf = $row->user_id && $row->user_id === Auth::id();
        $now    = now();

        $locked           = false;
        $remainingSeconds = 0;

        if ($cfg['enabled'] && $row->expires_at && $row->expires_at->isFuture()) {
            if ($isSelf) {
                if ($cfg['same_user_minutes'] > 0) {
                    $selfExpiry = $row->locked_at->copy()->addMinutes($cfg['same_user_minutes']);
                    if ($selfExpiry->isFuture()) {
                        $locked           = true;
                        $remainingSeconds = (int) ceil($now->diffInSeconds($selfExpiry));
                    }
                }
            } else {
                $locked           = true;
                $remainingSeconds = (int) ceil($now->diffInSeconds($row->expires_at));
            }
        }

        return response()->json([
            'callCount'        => (int) $row->call_count,
            'lastCalledAgo'    => $row->locked_at ? $row->locked_at->diffForHumans() : null,
            'lastCalledBy'     => $row->user_name,
            'locked'           => $locked,
            'lockedBySelf'     => $isSelf && $locked,
            'lockedBy'         => $locked ? $row->user_name : null,
            'remainingSeconds' => $remainingSeconds,
        ]);
    }

    /** POST /dialing/acquire — try to start a call; lock or allow depending on per-user timers. */
    public function acquire(Request $request): JsonResponse
    {
        $request->validate(['number' => ['required', 'string', 'max:30']]);

        $number = trim($request->input('number'));
        $key    = DialLock::keyFor($number);

        if (!$key) {
            return response()->json(['ok' => true, 'locked' => false, 'callCount' => 0]);
        }

        $cfg = $this->dialingSettings();

        if (!$cfg['enabled']) {
            return response()->json(['ok' => true, 'locked' => false, 'callCount' => 0]);
        }

        $user  = Auth::user();
        $now   = now();
        $until = $now->copy()->addMinutes($cfg['other_user_minutes']);

        $applicantId = Applicant::whereNull('deleted_at')
            ->phoneMatches($number)
            ->value('id');

        return DB::transaction(function () use ($key, $number, $user, $now, $until, $applicantId, $cfg) {
            $row    = DialLock::where('phone_key', $key)->lockForUpdate()->first();
            $isSelf = $row && $row->user_id === $user->id;

            if ($row && $row->expires_at && $row->expires_at->isFuture()) {
                // ── Same agent ────────────────────────────────────────────────
                if ($isSelf) {
                    if ($cfg['same_user_minutes'] > 0) {
                        $selfExpiry = $row->locked_at->copy()->addMinutes($cfg['same_user_minutes']);
                        if ($selfExpiry->isFuture()) {
                            $remaining = (int) ceil($now->diffInSeconds($selfExpiry));
                            $ago       = $row->locked_at ? $row->locked_at->diffForHumans() : 'just now';
                            return response()->json([
                                'ok'               => false,
                                'locked'           => true,
                                'lockedBySelf'     => true,
                                'lockedBy'         => $user->name,
                                'callCount'        => (int) $row->call_count,
                                'remainingSeconds' => $remaining,
                                'lastCalledAgo'    => $ago,
                                'message'          => "You already called this number {$ago}. "
                                                    . "Your re-dial lock expires in " . $this->humanSeconds($remaining) . ". "
                                                    . "Called " . (int) $row->call_count . "× in total.",
                            ], 423);
                        }
                    }
                    // same_user_minutes = 0 OR self-lock expired → fall through to allow
                } else {
                    // ── Different agent ───────────────────────────────────────
                    $remaining = (int) ceil($now->diffInSeconds($row->expires_at));
                    $ago       = $row->locked_at ? $row->locked_at->diffForHumans() : 'just now';
                    $who       = $row->user_name ?: 'another agent';

                    return response()->json([
                        'ok'               => false,
                        'locked'           => true,
                        'lockedBySelf'     => false,
                        'lockedBy'         => $who,
                        'callCount'        => (int) $row->call_count,
                        'remainingSeconds' => $remaining,
                        'lastCalledAgo'    => $ago,
                        'message'          => "This number is being called by {$who} ({$ago}). "
                                            . "Locked for another " . $this->humanSeconds($remaining) . ". "
                                            . "Called " . (int) $row->call_count . "× in total.",
                    ], 423);
                }
            }

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

    /** POST /dialing/release — free a lock the calling agent holds. */
    public function release(Request $request): JsonResponse
    {
        $request->validate(['number' => ['required', 'string', 'max:30']]);

        $key = DialLock::keyFor($request->input('number'));
        if ($key) {
            DialLock::where('phone_key', $key)
                ->where('user_id', Auth::id())
                ->update(['expires_at' => now()->subSecond()]);
        }

        return response()->json(['ok' => true]);
    }

    /** GET /dialing/active-locks — all currently active locks for the admin settings panel. */
    public function activeList(): JsonResponse
    {
        $locks = DialLock::where('expires_at', '>', now())
            ->orderBy('expires_at', 'asc')
            ->get()
            ->map(fn ($r) => [
                'id'               => $r->id,
                'full_number'      => $r->full_number,
                'user_name'        => $r->user_name ?: 'Unknown',
                'locked_at'        => $r->locked_at?->format('H:i:s'),
                'expires_at_iso'   => $r->expires_at?->toIso8601String(),
                'remaining_seconds'=> (int) ceil(now()->diffInSeconds($r->expires_at)),
                'call_count'       => (int) $r->call_count,
            ]);

        $callsToday = DialLock::whereDate('locked_at', today())->count();

        return response()->json([
            'locks'       => $locks,
            'stats'       => [
                'active_count' => $locks->count(),
                'calls_today'  => $callsToday,
            ],
        ]);
    }

    /** POST /dialing/clear-lock — admin: expire a specific lock immediately. */
    public function clearLock(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);
        DialLock::where('id', $request->id)->update(['expires_at' => now()->subSecond()]);
        return response()->json(['ok' => true]);
    }

    /** POST /dialing/clear-all-locks — admin: expire every active lock. */
    public function clearAllLocks(): JsonResponse
    {
        $count = DialLock::where('expires_at', '>', now())->count();
        DialLock::where('expires_at', '>', now())->update(['expires_at' => now()->subSecond()]);
        return response()->json(['ok' => true, 'cleared' => $count]);
    }

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
