<?php

namespace App\Support;

/**
 * Single source of truth for phone-number normalisation across the CRM.
 *
 * Caller-ID lookup, click-to-dial collision locks, and fuzzy applicant search
 * all need to decide when two differently-formatted numbers (+44…, 0…, 0044…,
 * with spaces/brackets) are "the same line". Centralising the rules here keeps
 * every feature in agreement and removes the duplicated preg_replace/substr
 * logic that previously lived in three controllers and two models.
 */
class PhoneNumber
{
    /** Strip everything except digits. Always returns a string ('' when empty). */
    public static function digits(?string $number): string
    {
        return preg_replace('/\D/', '', (string) $number);
    }

    /**
     * Last {$length} digits, used for fuzzy "same number" matching regardless
     * of international prefix. Returns null when there aren't enough digits to
     * be meaningful (mirrors the >= 7 guard used by the old lookup queries).
     */
    public static function tail(?string $number, int $length = 10): ?string
    {
        $digits = self::digits($number);
        if (strlen($digits) < 7) {
            return null;
        }
        return substr($digits, -$length);
    }

    /**
     * Canonical value stored in the indexed *_normalized columns: the last 10
     * digits (so RIGHT(digits,10) on the DB side matches), or null when empty.
     */
    public static function normalize(?string $number): ?string
    {
        $digits = self::digits($number);
        return $digits === '' ? null : substr($digits, -10);
    }

    /**
     * Collision-lock key. Real numbers collapse to their last 10 digits (so
     * +44…, 0…, 0044… of the same line collide); short internal extensions are
     * used as-is. Returns null when there is nothing meaningful to lock.
     */
    public static function lockKey(?string $number): ?string
    {
        $digits = self::digits($number);
        if (strlen($digits) < 3) {
            return null; // nothing meaningful to lock
        }
        return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
    }
}
