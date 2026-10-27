<?php

namespace App\Services;

use Carbon\Carbon;
use Horsefly\LoginDetail;
use Horsefly\User;

/**
 * Attendance from login_details: the first login of the day is the check-in, the last logout the
 * check-out. A shut-down PC never sends a logout, so the last activity (see touch()) stands in for it.
 */
class AttendanceTracker
{
    /** No activity for this long ends a working session (same as SESSION_LIFETIME). */
    public const SESSION_GAP_MINUTES = 120;

    /** Activity within this many minutes shows the user as online in the report. */
    public const ONLINE_MINUTES = 10;

    /** last_activity_at is written at most once per this many seconds per user. */
    private const WRITE_EVERY_SECONDS = 60;

    /** The user is using the portal: move the day's last activity forward (check in if there is no row yet). */
    public function touch(User $user, string $ip): void
    {
        $now = Carbon::now();
        $row = $this->currentRow($user->id, $now);

        if (!$row) {
            // Still logged in from an earlier day (e.g. "remember me"): first activity today is the check-in.
            LoginDetail::create([
                'user_id' => $user->id,
                'ip_address' => $ip,
                'login_at' => $now,
                'last_activity_at' => $now,
            ]);

            return;
        }

        if ($row->last_activity_at && $row->last_activity_at->gt($now->copy()->subSeconds(self::WRITE_EVERY_SECONDS))) {
            return;
        }

        $row->last_activity_at = $now;
        $row->save();
    }

    /** The user logged out: that is the check-out (a later login the same day clears it again). */
    public function checkout(User $user): void
    {
        $now = Carbon::now();
        $row = $this->currentRow($user->id, $now);

        if ($row) {
            $row->logout_at = $now;
            $row->last_activity_at = $now;
            $row->checkout_source = 'logout';
            $row->save();
        }
    }

    /** Today's row, or an earlier day's row whose session is still going (work past midnight). */
    public function currentRow(int $userId, Carbon $now): ?LoginDetail
    {
        $row = LoginDetail::where('user_id', $userId)->latest('id')->first();

        if (!$row) {
            return null;
        }
        if ($row->created_at && $row->created_at->isSameDay($now)) {
            return $row;
        }

        $stillGoing = $row->logout_at === null
            && $row->last_activity_at
            && $row->last_activity_at->gte($now->copy()->subMinutes(self::SESSION_GAP_MINUTES));

        return $stillGoing ? $row : null;
    }
}
