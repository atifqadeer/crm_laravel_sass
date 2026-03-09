<?php

$file = 'app/Http/Controllers/SaleController.php';
$content = file_get_contents($file);

// 1. We match getSales function up to the where conditions
$pattern1 = '/\/\/\s*Subquery to get the latest audit \(open_date\) for each sale(.*?)\$latestAuditSub = DB::table\(\'audits\'\).*?\]\);\s*if \(\$request->has\(\'search\.value\'\)\)/s';

// We want to replace the joins in getSales with the proper derived table joins
$replacement1 = '
        $model = Sale::query()
            ->select([
                \'sales.*\',
                \'job_titles.name as job_title_name\',
                \'job_categories.name as job_category_name\',
                \'offices.office_name as office_name\',
                \'units.unit_name as unit_name\',
                \'users.name as user_name\',
                \'audits.created_at as open_date\',
                
                \'updated_notes.id as latest_note_id\',
                \'updated_notes.sale_note as latest_note\',
                \'updated_notes.created_at as latest_note_time\',
            ])
            ->leftJoin(\'job_titles\', \'sales.job_title_id\', \'=\', \'job_titles.id\')
            ->leftJoin(\'job_categories\', \'sales.job_category_id\', \'=\', \'job_categories.id\')
            ->leftJoin(\'offices\', \'sales.office_id\', \'=\', \'offices.id\')
            ->leftJoin(\'units\', \'sales.unit_id\', \'=\', \'units.id\')
            ->leftJoin(\'users\', \'sales.user_id\', \'=\', \'users.id\')
            // Join only the latest audit for each sale avoiding ON subqueries and preventing duplicates completely
            ->leftJoin(DB::raw(\'(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type="Horsefly\\\\\\\\Sale" AND message LIKE "%sale-opened%" GROUP BY auditable_id) as latest_audits\'), \'sales.id\', \'=\', \'latest_audits.auditable_id\')
            ->leftJoin(\'audits\', \'audits.id\', \'=\', \'latest_audits.max_id\')
            ->with([\'jobTitle\', \'jobCategory\', \'unit\', \'office\', \'user\', \'saleNotes\'])
            // Subquery to get latest sale_note id per sale
            ->leftJoin(DB::raw(\'
                (SELECT sale_id, MAX(id) AS latest_id
                FROM sale_notes
                GROUP BY sale_id) AS latest_notes
            \'), \'sales.id\', \'=\', \'latest_notes.sale_id\')
            // Join the actual sale_notes record
            ->leftJoin(\'sale_notes AS updated_notes\', \'updated_notes.id\', \'=\', \'latest_notes.latest_id\')
            ->selectRaw(DB::raw("(SELECT COUNT(*) FROM cv_notes WHERE cv_notes.sale_id = sales.id AND cv_notes.status = 1) as no_of_sent_cv"))->distinct();

        if ($request->has(\'search.value\'))';

$new_content = preg_replace($pattern1, $replacement1, $content, 1, $count1);

echo "Replaced getSales: " . $count1 . "\n";
if ($count1) {
    file_put_contents($file, $new_content);
}
