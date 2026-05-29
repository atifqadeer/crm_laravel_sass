<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Horsefly\User;

/**
 * Registers the deployment server's own IP so the admin account can always log in.
 *
 * Also adds the common Docker-internal and private-network prefixes to the
 * allowed_ips table so the CRM is accessible from LAN / Docker Desktop
 * without manual IP registration.
 */
class DeploymentIpSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('is_admin', 1)->first();

        if (!$admin) {
            return;
        }

        // ── Exact IPs for the ip_addresses whitelist ──────────────────────────
        // Includes localhost variants and Docker gateway addresses.
        $exactIps = array_unique(array_filter([
            '127.0.0.1',
            '::1',
            '172.17.0.1',     // Docker default bridge gateway
            '172.18.0.1',     // Docker compose network gateway (often)
            '172.19.0.1',
            '172.20.0.1',
            '10.0.0.1',
            gethostbyname(gethostname()),   // container / server hostname resolution
        ]));

        foreach ($exactIps as $ip) {
            DB::table('ip_addresses')->insertOrIgnore([
                'user_id'    => $admin->id,
                'ip_address' => $ip,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── IP prefixes for the allowed_ips subnet whitelist ──────────────────
        // Covers the most common private-network /24 subnets so any device on
        // the same LAN (192.168.x, 10.x, Docker 172.x) can reach the login page.
        $prefixes = [
            '127.0.0',
            '172.17.0',
            '172.18.0',
            '172.19.0',
            '172.20.0',
            '172.21.0',
            '172.22.0',
            '10.0.0',
            '10.0.1',
            '192.168.0',
            '192.168.1',
            '192.168.2',
            '192.168.100',
            '192.168.110',
        ];

        foreach ($prefixes as $prefix) {
            DB::table('allowed_ips')->insertOrIgnore([
                'ip_prefix'  => $prefix,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
