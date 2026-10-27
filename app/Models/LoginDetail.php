<?php

namespace Horsefly;

use App\Services\AttendanceTracker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class LoginDetail extends Model
{
    protected $table = 'login_details';
    protected $fillable = [
        'user_id',
        'ip_address',
        'login_at',
        'logout_at',
        'last_activity_at',
        'checkout_source',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Check-in: the day's first login. */
    public function checkInAt(): ?Carbon
    {
        return $this->login_at ? $this->onRowDay($this->login_at) : null;
    }

    /**
     * Check-out as [time, status]:
     *  - 'logout': the user logged out;
     *  - 'auto':   no logout (PC shut down / browser closed), so the last activity in the portal;
     *  - 'online': no logout and active in the last few minutes (time = now, for the hours so far);
     *  - 'none':   nothing recorded (rows from before activity tracking).
     *
     * @return array{0: ?Carbon, 1: string}
     */
    public function checkOut(): array
    {
        if ($this->logout_at) {
            $at = $this->onRowDay($this->logout_at);
            $checkIn = $this->checkInAt();
            if ($checkIn && $at->lt($checkIn)) {
                $at->addDay(); // logged out after midnight
            }

            return [$at, $this->checkout_source === 'auto' ? 'auto' : 'logout'];
        }

        if ($this->last_activity_at) {
            if ($this->last_activity_at->gte(Carbon::now()->subMinutes(AttendanceTracker::ONLINE_MINUTES))) {
                return [Carbon::now(), 'online'];
            }

            return [$this->last_activity_at->copy(), 'auto'];
        }

        return [null, 'none'];
    }

    /** Check-out cell for the reports. */
    public function checkOutLabel(): string
    {
        [$at, $status] = $this->checkOut();

        return match ($status) {
            'logout' => $at->format('H:i:s'),
            'auto' => $at->format('H:i:s') . ' <span class="badge bg-warning-subtle text-warning" title="No logout: time of the last activity in the portal">Auto</span>',
            'online' => '<span class="badge bg-success-subtle text-success">Online</span>',
            default => '-',
        };
    }

    public function creditHours(): string
    {
        $checkIn = $this->checkInAt();
        [$checkOut] = $this->checkOut();

        if (!$checkIn || !$checkOut || $checkOut->lt($checkIn)) {
            return '0h 0m';
        }

        $minutes = (int) $checkIn->diffInMinutes($checkOut);

        return intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm';
    }

    /** A TIME column value on the day this row is for. */
    private function onRowDay(string $time): Carbon
    {
        return Carbon::parse(($this->created_at ?? Carbon::now())->toDateString() . ' ' . $time);
    }
}
