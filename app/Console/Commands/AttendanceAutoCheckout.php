<?php

namespace App\Console\Commands;

use App\Services\AttendanceTracker;
use Carbon\Carbon;
use Horsefly\LoginDetail;
use Illuminate\Console\Command;

class AttendanceAutoCheckout extends Command
{
    protected $signature = 'attendance:auto-checkout';

    protected $description = 'Fill the check-out of past days without a logout with the last activity in the portal';

    public function handle(): int
    {
        $now = Carbon::now();
        $closed = 0;

        // Days before today, idle for longer than a session lasts (work past midnight is left open until then).
        LoginDetail::query()
            ->whereNull('logout_at')
            ->whereNotNull('last_activity_at')
            ->where('last_activity_at', '<', $now->copy()->subMinutes(AttendanceTracker::SESSION_GAP_MINUTES))
            ->whereDate('created_at', '<', $now->toDateString())
            ->chunkById(500, function ($rows) use (&$closed) {
                foreach ($rows as $row) {
                    $row->logout_at = $row->last_activity_at->format('H:i:s');
                    $row->checkout_source = 'auto';
                    $row->save();
                    $closed++;
                }
            });

        $this->info("Auto check-out set on {$closed} row(s).");

        return self::SUCCESS;
    }
}
