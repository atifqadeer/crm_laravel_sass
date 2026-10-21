<?php

namespace App\Support;

class StatusBadge
{
    /**
     * The record's current status badge, followed by a Blacklisted badge when it is
     * blacklisted, so the list shows both (e.g. "Active" + "Blacklisted").
     */
    public static function withBlacklist(string $statusBadge, bool $isBlacklisted): string
    {
        if (!$isBlacklisted) {
            return $statusBadge;
        }

        return '<div class="d-flex flex-wrap gap-1">' . $statusBadge . self::blacklisted() . '</div>';
    }

    public static function blacklisted(): string
    {
        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">'
            . '<i class="ri-forbid-line me-1"></i>Blacklisted</span>';
    }
}
