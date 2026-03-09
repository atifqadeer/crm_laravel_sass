<?php

$file = 'app/Http/Controllers/SaleController.php';
$content = file_get_contents($file);

$pattern = '/\$query = Sale::query\(\)\s*->select\(\[\s*\'sales\.\*\',\s*\'job_titles\.name as job_title_name\',\s*\'job_categories\.name as job_category_name\',\s*\'offices\.office_name as office_name\',\s*\'units\.unit_name as unit_name\',\s*\'users\.name as user_name\',\s*\'audits\.created_at as ([^\']+)\',\s*\/\/ Sale notes subquery\s*\'latest_notes\.id as latest_note_id\',\s*\'latest_notes\.sale_note as latest_note\',\s*\'latest_notes\.created_at as latest_note_time\'\s*\]\)\s*->leftJoin\(\'job_titles\', \'sales\.job_title_id\', \'=\', \'job_titles\.id\'\)\s*->leftJoin\(\'job_categories\', \'sales\.job_category_id\', \'=\', \'job_categories\.id\'\)\s*->leftJoin\(\'offices\', \'sales\.office_id\', \'=\', \'offices\.id\'\)\s*->leftJoin\(\'units\', \'sales\.unit_id\', \'=\', \'units\.id\'\)\s*->leftJoin\(\'users\', \'sales\.user_id\', \'=\', \'users\.id\'\)\s*\/\/ Join latest audit for each sale\s*->leftJoin\(\'audits\', function \(\$join\) \{\s*\$join->on\(\'audits\.auditable_id\', \'=\', \'sales\.id\'\)\s*->where\(\'audits\.auditable_type\', \'Horsefly\\\\\\\\Sale\'\)\s*->where\(\'audits\.message\', \'like\', \'([^\']+)\'\);\s*\}\)\s*\/\/ Subquery to get latest sale note\s*->leftJoin\(\s*DB::raw\(\"\(SELECT sale_id, MAX\(id\) AS latest_id FROM sale_notes GROUP BY sale_id\) AS latest_notes\"\),\s*\'sales\.id\',\s*\'=\',\s*\'latest_notes\.sale_id\'\s*\)\s*->leftJoin\(\'sale_notes as latest_notes\', \'latest_notes\.id\', \'=\', \'latest_notes\.latest_id\'\)\s*\/\/ Add custom count for CV notes\s*->addSelect\(\[\s*DB::raw\(\"\(SELECT COUNT\(\*\) FROM cv_notes WHERE cv_notes\.sale_id = sales\.id AND cv_notes\.status = 1\) as no_of_sent_cv\"\)\s*\]\);/s';

$replacement = "\$query = Sale::query()
            ->select([
                'sales.*',
                'job_titles.name as job_title_name',
                'job_categories.name as job_category_name',
                'offices.office_name as office_name',
                'units.unit_name as unit_name',
                'users.name as user_name',
            ])
            ->leftJoin('job_titles', 'sales.job_title_id', '=', 'job_titles.id')
            ->leftJoin('job_categories', 'sales.job_category_id', '=', 'job_categories.id')
            ->leftJoin('offices', 'sales.office_id', '=', 'offices.id')
            ->leftJoin('units', 'sales.unit_id', '=', 'units.id')
            ->leftJoin('users', 'sales.user_id', '=', 'users.id')
            ->addSelect([
                \$1 => \DB::table('audits')
                    ->select('created_at')
                    ->whereColumn('auditable_id', 'sales.id')
                    ->where('auditable_type', 'Horsefly\\\\Sale')
                    ->where('message', 'like', '\$2')
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
    // try a more generic replacement because spacing might differ
    echo "No matches with strict pattern.\n";
}
