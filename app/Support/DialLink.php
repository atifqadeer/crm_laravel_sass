<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;

/**
 * Renders the click-to-dial `<a>` markup used in every applicant phone
 * column. By default the real number is MASKED in the rendered HTML
 * (e.g. ••••••789) and the only machine-readable copy is an encrypted token
 * that the xplosip widget hands back to DialLockController (decrypted
 * server-side at dial time).
 *
 * The masking is permission-controlled: a user whose role has the
 * `applicant-view-phone-number` permission (tick the checkbox in the role
 * editor) sees the real digits; everyone else sees the masked form. The
 * check is resolved here so every phone column across the app honors the
 * permission with no per-controller wiring. (Spatie caches the permission
 * lookup per request, so the per-row call is cheap.)
 */
class DialLink
{
    public static function render(?string $number, string $label, string $class = ''): string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return '';
        }

        $reveal = Gate::allows('applicant-view-phone-number');

        $display = $reveal ? e($number) : e(PhoneNumber::mask($number));
        $token   = e(Crypt::encryptString($number));
        $xplabel = e($label . ' ' . PhoneNumber::mask($number));

        return "<strong title=\"{$label}\">" . substr($label, 0, 1) . ':</strong> '
            . "<a href=\"javascript:void(0)\"
            data-xpdial=\"{$token}\"
            data-xplabel=\"{$xplabel}\"
            onclick=\"if(window.xplosipDial){xplosipDial('{$number}','{$display}');}\"
            class=\"text-decoration-none {$class}\"
            title=\"Click to dial\">{$display}</a>";
    }

    public static function resolve(?string $token): ?string
    {
        if (!is_string($token) || $token === '') {
            return null;
        }

        try {
            return Crypt::decryptString($token);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
