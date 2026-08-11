<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            if (!Schema::hasColumn('regions', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('districts_code');
            }
            if (!Schema::hasColumn('regions', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('regions', 'radius')) {
                // km — used as an optional max distance for Haversine assignment
                $table->unsignedInteger('radius')->nullable()->after('longitude');
            }
        });

        // Approximate UK region centroids so openSalesApi can resolve names immediately.
        $centroids = [
            'Scotland' => [56.4907, -4.2026, 250],
            'Northern Ireland' => [54.7877, -6.4923, 120],
            'Wales' => [52.1307, -3.7837, 150],
            'North East' => [54.9783, -1.6178, 100],
            'North West' => [53.4808, -2.2426, 120],
            'West Midlands' => [52.4862, -1.8904, 100],
            'East Midlands' => [52.9548, -1.1581, 110],
            'South West' => [50.7156, -3.5309, 160],
            'South East' => [51.2787, 0.5217, 120],
            'East of England' => [52.2053, 0.1218, 120],
            'Greater London' => [51.5074, -0.1278, 40],
        ];

        foreach ($centroids as $name => [$lat, $lng, $radius]) {
            DB::table('regions')
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->update([
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'radius' => $radius,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            if (Schema::hasColumn('regions', 'radius')) {
                $table->dropColumn('radius');
            }
            if (Schema::hasColumn('regions', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('regions', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};
