<?php

$file = 'app/Http/Controllers/SaleController.php';
$content = file_get_contents($file);

// Find and replace the subqueries inside getDirectSales, getRejectedSales, getClosedSales, getOpenSales

$pattern = '/->leftJoin\(\'audits\', function \(\$join\) \{\s*\$join->on\(\'audits\.auditable_id\', \'=\', \'sales\.id\'\)\s*->where\(\'audits\.auditable_type\', \'Horsefly\\\\\\\\Sale\'\)\s*->where\(\'audits\.message\', \'like\', \'([^\']+)\'\);\s*\}\)\s*\/\/ Subquery to get latest sale note\s*->leftJoin\(\s*DB::raw\(\"\(SELECT sale_id, MAX\(id\) AS latest_id FROM sale_notes GROUP BY sale_id\) AS latest_notes\"\),\s*\'sales\.id\',\s*\'=\',\s*\'latest_notes\.sale_id\'\s*\)\s*->leftJoin\(\'sale_notes as latest_notes\', \'latest_notes\.id\', \'=\', \'latest_notes\.latest_id\'\)\s*\/\/ Add custom count for CV notes\s*->addSelect\(\[\s*DB::raw\(\"\(SELECT COUNT\(\*\) FROM cv_notes WHERE cv_notes\.sale_id = sales\.id AND cv_notes\.status = 1\) as no_of_sent_cv\"\)\s*\]\);/s';

$replacement = "->addSelect([
                /* audits.created_at handled below */
                'open_date' => \DB::table('audits')
                    ->select('created_at')
                    ->whereColumn('auditable_id', 'sales.id')
                    ->where('auditable_type', 'Horsefly\\\\Sale')
                    ->where('message', 'like', '\$1')
                    ->orderByDesc('id')
                    ->limit(1),
                'latest_note_id' => \DB::table('sale_notes')
                    ->select('id')
                    ->whereColumn('sale_id', 'sales.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'latest_note' => \DB::table('sale_notes')
                    ->select('sale_note')
                    ->whereColumn('sale_id', 'sales.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'latest_note_time' => \DB::table('sale_notes')
                    ->select('created_at')
                    ->whereColumn('sale_id', 'sales.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'no_of_sent_cv' => \DB::table('cv_notes')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('sale_id', 'sales.id')
                    ->where('status', 1)
            ]);";

$new_content = preg_replace($pattern, $replacement, $content, -1, $count);

echo "Replaced $count occurrences.\n";

if ($count > 0) {
    file_put_contents($file, $new_content);
    echo "Saved optimizations!";
} else {
    echo "No matches with strict pattern.\n";
}
