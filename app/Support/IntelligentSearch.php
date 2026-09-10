<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Token-aware search for applicant lists.
 *
 * Names are matched by the full phrase, by the first and last words of the
 * stored value (so "John Smith" finds "John Michael Smith"), and by those
 * similar words appearing in any order. The same token logic is applied to
 * other text columns so a hit is never dropped just because the name column
 * did not contain the contiguous phrase.
 */
class IntelligentSearch
{
    /**
     * Stop Yajra from AND-ing its default column LIKE on top of apply().
     * Without this, first/last-name matches are dropped when the full phrase
     * is not a contiguous substring of applicant_name.
     *
     * @param  \Yajra\DataTables\DataTableAbstract  $dataTable
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public static function withoutDefaultFilter($dataTable)
    {
        return $dataTable->filter(static function () {
            // Search was already applied on the Eloquent query.
        }, false);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array{
     *     name?: string[],
     *     text?: string[],
     *     emails?: string[],
     *     phones?: string[],
     *     postcodes?: string[],
     * }  $columns
     */
    public static function apply($query, string $term, array $columns): void
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');

        if (mb_strlen($term) < 2) {
            return;
        }

        $words = self::words($term);

        $query->where(function ($q) use ($term, $words, $columns) {
            foreach ($columns['name'] ?? [] as $column) {
                self::matchName($q, $column, $term, $words);
            }

            foreach ($columns['text'] ?? [] as $column) {
                self::matchText($q, $column, $term, $words);
            }

            self::matchEmails($q, $columns['emails'] ?? [], $term, $words);
            self::matchPhones($q, $columns['phones'] ?? [], $term);
            self::matchPostcodes($q, $columns['postcodes'] ?? [], $term);
        });
    }

    /**
     * Default applicant-list columns. Pass extraText / extra name-style
     * columns that are already joined into the query.
     *
     * @param  array{extraText?: string[], extraName?: string[], extraEmails?: string[], extraPhones?: string[], extraPostcodes?: string[]}  $extra
     * @return array{name: string[], text: string[], emails: string[], phones: string[], postcodes: string[]}
     */
    public static function applicantColumns(array $extra = []): array
    {
        return [
            'name' => array_merge(
                ['applicants.applicant_name'],
                $extra['extraName'] ?? []
            ),
            'emails' => array_merge(
                [
                    'applicants.applicant_email',
                    'applicants.applicant_email_secondary',
                ],
                $extra['extraEmails'] ?? []
            ),
            'text' => array_merge(
                [
                    'applicants.applicant_notes',
                    'applicants.applicant_experience',
                    'job_titles.name',
                    'job_categories.name',
                    'job_sources.name',
                ],
                $extra['extraText'] ?? []
            ),
            'phones' => array_merge(
                [
                    'applicants.applicant_phone',
                    'applicants.applicant_phone_secondary',
                    'applicants.applicant_landline',
                ],
                $extra['extraPhones'] ?? []
            ),
            'postcodes' => array_merge(
                ['applicants.applicant_postcode'],
                $extra['extraPostcodes'] ?? []
            ),
        ];
    }

    /**
     * Match a person-name column: phrase, first+last index, and similar words.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string[]  $words
     */
    public static function matchName($query, string $column, string $term, ?array $words = null): void
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
        $words = $words ?? self::words($term);
        $wrapped = self::wrapColumn($column);
        $normalized = self::normalizedSql($wrapped);

        $query->orWhere(function ($q) use ($column, $normalized, $term, $words) {
            $q->where($column, 'LIKE', '%' . self::escapeLike($term) . '%');

            if (count($words) >= 2) {
                $first = strtolower(self::escapeLike($words[0]));
                $last = strtolower(self::escapeLike($words[count($words) - 1]));

                $q->orWhere(function ($nameQ) use ($normalized, $first, $last) {
                    $nameQ->whereRaw("LOWER(SUBSTRING_INDEX({$normalized}, ' ', 1)) LIKE ?", [$first . '%'])
                        ->whereRaw("LOWER(SUBSTRING_INDEX({$normalized}, ' ', -1)) LIKE ?", [$last . '%']);
                });

                $q->orWhere(function ($nameQ) use ($normalized, $words) {
                    foreach ($words as $word) {
                        $nameQ->whereRaw(
                            "CONCAT(' ', {$normalized}, ' ') LIKE ?",
                            ['% ' . strtolower(self::escapeLike($word)) . ' %']
                        );
                    }
                });
            }
        });
    }

    /**
     * Match a non-name text column with the same phrase + token logic.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string[]  $words
     */
    public static function matchText($query, string $column, string $term, ?array $words = null): void
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
        $words = $words ?? self::words($term);
        $wrapped = self::wrapColumn($column);
        $normalized = self::normalizedSql($wrapped);

        $query->orWhere(function ($q) use ($column, $normalized, $term, $words) {
            $q->where($column, 'LIKE', '%' . self::escapeLike($term) . '%');

            if (count($words) >= 2) {
                $first = strtolower(self::escapeLike($words[0]));
                $last = strtolower(self::escapeLike($words[count($words) - 1]));

                $q->orWhere(function ($textQ) use ($normalized, $first, $last) {
                    $textQ->whereRaw("LOWER(SUBSTRING_INDEX({$normalized}, ' ', 1)) LIKE ?", [$first . '%'])
                        ->whereRaw("LOWER(SUBSTRING_INDEX({$normalized}, ' ', -1)) LIKE ?", [$last . '%']);
                });

                $q->orWhere(function ($textQ) use ($normalized, $words) {
                    foreach ($words as $word) {
                        $textQ->whereRaw(
                            "CONCAT(' ', {$normalized}, ' ') LIKE ?",
                            ['% ' . strtolower(self::escapeLike($word)) . ' %']
                        );
                    }
                });
            }
        });
    }

    /**
     * Emails must not use first/last-word matching: `@` and `.` are not name tokens,
     * and wrapping the value in spaces makes `john@x.com` miss `john@x.com`.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string[]  $columns
     * @param  string[]  $words
     */
    public static function matchEmails($query, array $columns, string $term, ?array $words = null): void
    {
        if ($columns === []) {
            return;
        }

        $words = $words ?? self::words($term);
        $candidates = self::emailCandidates($term, $words);

        if ($candidates === []) {
            return;
        }

        foreach ($columns as $column) {
            $wrapped = self::wrapColumn($column);

            foreach ($candidates as $candidate) {
                $query->orWhereRaw(
                    "LOWER({$wrapped}) LIKE ?",
                    ['%' . mb_strtolower(self::escapeLike($candidate)) . '%']
                );
            }
        }
    }

    /**
     * @param  string[]  $words
     * @return string[]
     */
    private static function emailCandidates(string $term, array $words): array
    {
        $candidates = [$term];

        foreach ($words as $word) {
            $candidates[] = $word;
        }

        if (str_contains($term, '@')) {
            $local = strstr($term, '@', true);
            if (is_string($local) && $local !== '') {
                $candidates[] = $local;
            }

            $domain = substr(strstr($term, '@') ?: '', 1);
            if (is_string($domain) && $domain !== '') {
                $candidates[] = $domain;
            }
        }

        foreach ($words as $word) {
            if (!str_contains($word, '@')) {
                continue;
            }
            $local = strstr($word, '@', true);
            if (is_string($local) && $local !== '') {
                $candidates[] = $local;
            }
        }

        $unique = [];
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if (mb_strlen($candidate) < 2) {
                continue;
            }
            $unique[mb_strtolower($candidate)] = $candidate;
        }

        return array_values($unique);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string[]  $columns
     */
    private static function matchPhones($query, array $columns, string $term): void
    {
        $digits = preg_replace('/\D+/', '', $term) ?? '';

        if ($digits === '' || strlen($digits) < 2 || $columns === []) {
            return;
        }

        foreach ($columns as $column) {
            $wrapped = self::wrapColumn($column);
            $query->orWhereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE({$wrapped}, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?",
                ['%' . $digits . '%']
            );
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string[]  $columns
     */
    private static function matchPostcodes($query, array $columns, string $term): void
    {
        if ($columns === []) {
            return;
        }

        $normalized = strtoupper(preg_replace('/[\s\-]/', '', $term) ?? '');

        if ($normalized === '') {
            return;
        }

        foreach ($columns as $column) {
            $wrapped = self::wrapColumn($column);
            $query->orWhereRaw(
                "REPLACE(REPLACE(UPPER({$wrapped}), ' ', ''), '-', '') LIKE ?",
                ['%' . self::escapeLike($normalized) . '%']
            );
        }
    }

    /**
     * @return string[]
     */
    public static function words(string $term): array
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');

        if ($term === '') {
            return [];
        }

        return array_values(array_filter(
            explode(' ', $term),
            fn (string $word) => mb_strlen($word) >= 2
        ));
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function wrapColumn(string $column): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?$/', $column)) {
            throw new InvalidArgumentException("Invalid search column: {$column}");
        }

        return collect(explode('.', $column))
            ->map(fn (string $part) => '`' . $part . '`')
            ->implode('.');
    }

    /**
     * Collapse punctuation/hyphens/extra spaces so first/last word indexes
     * and whole-word LIKE checks see a stable token stream.
     */
    private static function normalizedSql(string $wrappedColumn): string
    {
        return "TRIM(REGEXP_REPLACE(LOWER({$wrappedColumn}), '[^a-z0-9]+', ' '))";
    }
}
