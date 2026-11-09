<?php

namespace App\Support;

use Illuminate\Mail\Message;

/**
 * Embeds the email signature image in the message itself, so inboxes show it without
 * having to download it from the CRM. Saved emails keep their normal image URL.
 */
class EmailInlineImages
{
    private const SIGNATURE_IMAGE_PATTERN = '#(?:^|/)(images/Kingsbury-Personnel-Logo\.png|uploads/email-signatures/[^/]+|storage/.+)$#';

    public static function embed(Message $message, ?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $embedded = [];

        return preg_replace_callback(
            '/(<img\b[^>]*?\bsrc\s*=\s*)(["\'])(.*?)\2/is',
            function ($matches) use ($message, &$embedded) {
                $file = self::localFile(html_entity_decode($matches[3], ENT_QUOTES));
                if ($file === null) {
                    return $matches[0];
                }

                $embedded[$file] ??= $message->embed($file);

                return $matches[1] . $matches[2] . $embedded[$file] . $matches[2];
            },
            $html
        ) ?? $html;
    }

    /** The image on disk for a signature image URL, whichever host the URL was built with. */
    private static function localFile(string $source): ?string
    {
        if (preg_match('/^(data|cid):/i', $source)) {
            return null;
        }

        $path = rawurldecode((string) parse_url($source, PHP_URL_PATH));
        if (!preg_match(self::SIGNATURE_IMAGE_PATTERN, $path, $matches)) {
            return null;
        }

        $isStorage = str_starts_with($matches[1], 'storage/');
        $root = realpath($isStorage ? storage_path('app/public') : public_path());
        $file = realpath(
            $isStorage
                ? storage_path('app/public/' . substr($matches[1], strlen('storage/')))
                : public_path($matches[1])
        );

        if ($root === false || $file === false || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($file) && str_starts_with((string) mime_content_type($file), 'image/') ? $file : null;
    }
}
