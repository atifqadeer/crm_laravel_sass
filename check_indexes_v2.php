<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['sales', 'cv_notes', 'job_titles', 'job_categories', 'offices', 'units', 'users'] as $tableName) {
    echo "Table: $tableName\n";
    $indexes = DB::select("SHOW INDEX FROM $tableName");
    foreach ($indexes as $index) {
        echo "  Index: " . $index->Key_name . " (Column: " . $index->Column_name . ")\n";
    }
}
