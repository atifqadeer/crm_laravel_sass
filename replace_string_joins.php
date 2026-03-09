<?php

$file = 'app/Http/Controllers/SaleController.php';
$content = file_get_contents($file);

function safe_replace($target, $replacement, &$content) {
    if (strpos($content, $target) !== false) {
        $content = str_replace($target, $replacement, $content);
        return true;
    }
    return false;
}

$find_direct_sales = "            ->leftJoin('audits', function (\$join) use (\$latestAuditSub) {
            \$join->on('audits.auditable_id', '=', 'sales.id')
                ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                ->where('audits.message', 'like', '%sale-opened%')
                ->whereIn('audits.id', \$latestAuditSub);
            })";

$replace_direct_sales = "            ->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\Sale\" AND message LIKE \"%sale-opened%\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')
            ->groupBy('sales.id')";

if (safe_replace($find_direct_sales, $replace_direct_sales, $content)) {
    echo "Replaced Direct Sales audits join.\n";
} else {
    echo "Failed Direct Sales audits join.\n";
}

$find_get_sales_audits = "             // Join only the latest audit for each sale
            ->leftJoin('audits', function (\$join) use (\$latestAuditSub) {
            \$join->on('audits.auditable_id', '=', 'sales.id')
                ->where('audits.auditable_type', '=', 'Horsefly\\\\Sale')
                ->where('audits.message', 'like', '%sale-opened%')
                ->whereIn('audits.id', \$latestAuditSub);
            })";

$replace_get_sales_audits = "             // Join only the latest audit for each sale avoiding duplicates
            ->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\\\\\Sale\" AND message LIKE \"%sale-opened%\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')
            ->groupBy('sales.id')";

if (safe_replace($find_get_sales_audits, $replace_get_sales_audits, $content)) {
    echo "Replaced Get Sales audits join.\n";
} else {
    echo "Failed Get Sales audits join.\n";
}

$find_rejected_sales = "            ->leftJoin('audits', function (\$join) use (\$latestAuditSub) {
            \$join->on('audits.auditable_id', '=', 'sales.id')
                ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                ->where('audits.message', 'like', '%sale-rejected%')
                ->whereIn('audits.id', \$latestAuditSub);
            })";

$replace_rejected_sales = "            ->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\Sale\" AND message LIKE \"%sale-rejected%\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')
            ->groupBy('sales.id')";

if (safe_replace($find_rejected_sales, $replace_rejected_sales, $content)) {
    echo "Replaced Rejected Sales audits join.\n";
} else {
    echo "Failed Rejected Sales audits join.\n";
}

$find_closed_sales = "            ->leftJoin('audits', function (\$join) use (\$latestAuditSub) {
            \$join->on('audits.auditable_id', '=', 'sales.id')
                ->where('audits.auditable_type', '=', 'Horsefly\Sale')
                ->where('audits.message', 'like', '%sale-closed%')
                ->whereIn('audits.id', \$latestAuditSub);
            })";

$replace_closed_sales = "            ->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\Sale\" AND message LIKE \"%sale-closed%\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')
            ->groupBy('sales.id')";

if (safe_replace($find_closed_sales, $replace_closed_sales, $content)) {
    echo "Replaced Closed Sales audits join.\n";
} else {
    echo "Failed Closed Sales audits join.\n";
}

$find_open_sales = "            ->leftJoin('audits', function (\$join) use (\$latestAuditSub) {
                \$join->on('audits.auditable_id', '=', 'sales.id')
                    ->where('audits.auditable_type', '=', 'Horsefly\\\\Sale')
                    ->where('audits.message', 'like', '%sale-opened%')
                    ->whereIn('audits.id', \$latestAuditSub);
            })";

$replace_open_sales = "            ->leftJoin(DB::raw('(SELECT auditable_id, MAX(id) as max_id FROM audits WHERE auditable_type=\"Horsefly\\\\\\\\Sale\" AND message LIKE \"%sale-opened%\" GROUP BY auditable_id) as latest_audits'), 'sales.id', '=', 'latest_audits.auditable_id')
            ->leftJoin('audits', 'audits.id', '=', 'latest_audits.max_id')
            ->groupBy('sales.id')";

if (safe_replace($find_open_sales, $replace_open_sales, $content)) {
    echo "Replaced Open Sales audits join.\n";
} else {
    echo "Failed Open Sales audits join.\n";
}

file_put_contents($file, $content);

