<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Horsefly\Applicant;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Change this to a name you know exists in your DB
$testSearch = "John"; 

echo "Testing Scout Search for: '$testSearch'" . PHP_EOL;

try {
    $search = Applicant::search($testSearch);
    $ids = $search->keys()->toArray();
    
    echo "Results found: " . count($ids) . PHP_EOL;
    if (!empty($ids)) {
        echo "IDs found: " . implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? '...' : '') . PHP_EOL;
        
        // Check if these IDs actually exist in DB
        $count = DB::table('applicants')->whereIn('id', $ids)->count();
        echo "Valid DB matches: $count" . PHP_EOL;
    } else {
        echo "No IDs returned. Check if 'php artisan scout:import' was run on this environment." . PHP_EOL;
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
