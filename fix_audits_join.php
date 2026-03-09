<?php

$file = 'app/Http/Controllers/SaleController.php';
$content = file_get_contents($file);

$pattern = '/->leftJoin\(\'audits\', function \(\$join\) use \(\$latestAuditSub\) \{\s*\$join->on\(\'audits\.auditable_id\', \'=\', \'sales\.id\'\)\s*->where\(\'audits\.auditable_type\', \'=\', \'(?:Horsefly\\\\\\\\Sale|Horsefly\\\\Sale)\'\)\s*->where\(\'audits\.message\', \'like\', \'([^\']+)\'\)\s*->whereIn\(\'audits\.id\', \$latestAuditSub\);\s*\}\)/s';

$replacement = "->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\\\\\Sale\" AND message LIKE \"$1\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')";

$new_content = preg_replace($pattern, $replacement, $content, -1, $count);
echo "Replaced audits join: " . $count . "\n";

if ($count > 0) {
    file_put_contents($file, $new_content);
}
