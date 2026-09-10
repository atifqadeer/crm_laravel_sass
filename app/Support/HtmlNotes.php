<?php

namespace App\Support;

class HtmlNotes
{
    private const ALLOWED_TAGS = '<strong><b><br><i><em><u><p><div><ul><ol><li>';

    public static function toSafeHtml(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        if ($html === strip_tags($html)) {
            return nl2br(e($html), false);
        }

        return strip_tags($html, self::ALLOWED_TAGS);
    }

    public static function toPlainText(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return '';
        }

        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $withBreaks = preg_replace(
            '/<(\/?(p|div|li|br|ul|ol|tr|td|table|h[1-6]))[^>]*>/i',
            "\n",
            $html
        ) ?? $html;

        $plain = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES, 'UTF-8');
        $plain = preg_replace("/[ \t]+/", ' ', $plain) ?? $plain;
        $plain = preg_replace("/\n{2,}/", "\n", $plain) ?? $plain;

        return trim($plain);
    }
}
