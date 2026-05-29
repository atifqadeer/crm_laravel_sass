<?php

namespace App\Traits;

/**
 * Prevents CSV/Excel formula injection (a.k.a. CSV injection / formula injection).
 *
 * When a cell value begins with  =  +  -  @  TAB  CR  it is interpreted as a
 * formula by Excel, LibreOffice Calc, and Google Sheets.  A malicious value
 * such as  =cmd|'/c calc'!A0  can execute arbitrary commands on the victim's
 * machine when they open the exported file.
 *
 * The fix recommended by OWASP is to prefix any dangerous value with a single
 * quote ( ' ).  Excel treats a leading single-quote as a "text prefix" escape
 * and displays the literal string without triggering a formula.  The quote
 * itself is hidden from the user in the cell view.
 *
 * References:
 *   https://owasp.org/www-community/attacks/CSV_Injection
 *   https://cwe.mitre.org/data/definitions/1236.html
 */
trait SanitizesExportValues
{
    /**
     * Sanitize a single cell value against formula injection.
     *
     * @param  mixed  $value
     * @return mixed  Original value if safe; prefixed string if dangerous.
     */
    protected function sanitize(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Characters that trigger formula evaluation in spreadsheet apps.
        // TAB (\t) and CR (\r) are included because some parsers strip the
        // leading character and then re-evaluate the remainder.
        if (in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    /**
     * Sanitize every string value in an associative row array.
     *
     * @param  array  $row
     * @return array
     */
    protected function sanitizeRow(array $row): array
    {
        return array_map(fn ($cell) => $this->sanitize($cell), $row);
    }
}
