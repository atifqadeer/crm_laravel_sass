<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Horsefly\User;
use Horsefly\JobSource;
use Horsefly\JobCategory;
use Horsefly\JobTitle;

/**
 * DemoSeeder — seeds realistic demo data for first-time use.
 *
 * Creates:
 *   • 5 users (one per role)
 *   • Common IP whitelist entries
 *   • 3 job categories + 6 job titles + 3 job sources
 *   • 20 sample applicants
 *
 * Login credentials after seeding:
 *   Role         Email                  Password
 *   ──────────────────────────────────────────────
 *   super_admin  admin@crm.local        Admin@1234!
 *   admin        manager@crm.local      Admin@1234!
 *   sales        sales@crm.local        Admin@1234!
 *   crm          crm@crm.local          Admin@1234!
 *   quality      quality@crm.local      Admin@1234!
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Admin@1234!';

    public function run(): void
    {
        $this->command->info('  ↳ Creating demo users …');
        $users = $this->createDemoUsers();

        $this->command->info('  ↳ Registering IPs …');
        $this->seedIpWhitelist($users['admin']);

        $this->command->info('  ↳ Creating job categories, titles, sources …');
        [$categories, $titles, $sources] = $this->seedJobData();

        $this->command->info('  ↳ Creating 20 demo applicants …');
        $this->seedApplicants($users, $categories, $titles, $sources);

        $this->command->info('  ↳ Demo seed complete.');
        $this->command->info('');
        $this->command->info('  ┌─────────────────────────────────────────────┐');
        $this->command->info('  │  Demo login credentials                     │');
        $this->command->info('  │  Email:    admin@crm.local                  │');
        $this->command->info('  │  Password: ' . self::PASSWORD . '                  │');
        $this->command->info('  └─────────────────────────────────────────────┘');
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    private function createDemoUsers(): array
    {
        $pw   = Hash::make(self::PASSWORD);
        $now  = now();
        $users = [];

        $definitions = [
            'admin'   => ['name' => 'Admin User',    'email' => 'admin@crm.local',   'is_admin' => 1, 'role' => 'super_admin'],
            'manager' => ['name' => 'Office Manager','email' => 'manager@crm.local', 'is_admin' => 0, 'role' => 'admin'],
            'sales'   => ['name' => 'Sales User',    'email' => 'sales@crm.local',   'is_admin' => 0, 'role' => 'sales'],
            'crm'     => ['name' => 'CRM User',      'email' => 'crm@crm.local',     'is_admin' => 0, 'role' => 'crm'],
            'quality' => ['name' => 'Quality User',  'email' => 'quality@crm.local', 'is_admin' => 0, 'role' => 'quality'],
        ];

        // Silence model observers during seeding
        $dispatcher = User::getEventDispatcher();
        User::unsetEventDispatcher();

        foreach ($definitions as $key => $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name'               => $def['name'],
                    'email_verified_at'  => $now,
                    'is_admin'           => $def['is_admin'],
                    'is_active'          => 1,
                    'password'           => $pw,
                    'remember_token'     => Str::random(10),
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ]
            );

            // Assign role (safe to call multiple times)
            if ($role = Role::where('name', $def['role'])->first()) {
                $user->assignRole($role);
            }

            $users[$key] = $user;
        }

        User::setEventDispatcher($dispatcher);

        return $users;
    }

    // ── IP Whitelist ──────────────────────────────────────────────────────────

    private function seedIpWhitelist(User $admin): void
    {
        // Exact IPs — ip_addresses table (used by LoginController)
        $exactIps = [
            '127.0.0.1', '::1',
            '172.17.0.1', '172.18.0.1', '172.19.0.1', '172.20.0.1',
            '10.0.0.1', '10.0.0.2',
            '192.168.0.1', '192.168.1.1',
        ];

        foreach ($users = [$admin] as $user) {
            foreach ($exactIps as $ip) {
                DB::table('ip_addresses')->insertOrIgnore([
                    'user_id'    => $user->id,
                    'ip_address' => $ip,
                    'status'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Prefixes — allowed_ips table (used by IPAddress middleware)
        $prefixes = [
            '127.0.0', '::',
            '172.17.0', '172.18.0', '172.19.0', '172.20.0',
            '172.21.0', '172.22.0', '172.23.0', '172.24.0',
            '10.0.0', '10.0.1', '10.1.0',
            '192.168.0', '192.168.1', '192.168.2', '192.168.100', '192.168.110',
        ];

        foreach ($prefixes as $prefix) {
            DB::table('allowed_ips')->insertOrIgnore([
                'ip_prefix'  => $prefix,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    // ── Job reference data ────────────────────────────────────────────────────

    private function seedJobData(): array
    {
        $categories = [];
        $catData = ['Healthcare', 'Social Care', 'Administration'];
        foreach ($catData as $name) {
            $categories[] = JobCategory::firstOrCreate(['name' => $name], ['name' => $name]);
        }

        $titles = [];
        $titleData = [
            ['name' => 'Registered Nurse',       'type' => 'nurse',  'job_category_id' => $categories[0]->id],
            ['name' => 'Healthcare Assistant',   'type' => 'nurse',  'job_category_id' => $categories[0]->id],
            ['name' => 'Care Worker',            'type' => 'non_nurse', 'job_category_id' => $categories[1]->id],
            ['name' => 'Senior Care Worker',     'type' => 'non_nurse', 'job_category_id' => $categories[1]->id],
            ['name' => 'Office Administrator',   'type' => 'non_nurse', 'job_category_id' => $categories[2]->id],
            ['name' => 'Receptionist',           'type' => 'non_nurse', 'job_category_id' => $categories[2]->id],
        ];
        foreach ($titleData as $d) {
            $titles[] = JobTitle::firstOrCreate(['name' => $d['name']], $d);
        }

        $sources = [];
        $sourceData = [
            ['name' => 'Indeed'],
            ['name' => 'NHS Jobs'],
            ['name' => 'Referral'],
        ];
        foreach ($sourceData as $d) {
            $sources[] = JobSource::firstOrCreate(['name' => $d['name']], $d);
        }

        return [$categories, $titles, $sources];
    }

    // ── Applicants ────────────────────────────────────────────────────────────

    private function seedApplicants(array $users, array $categories, array $titles, array $sources): void
    {
        $demo = [
            ['name' => 'Emma Thompson',   'phone' => '07700900001', 'postcode' => 'SW1A 1AA', 'job_type' => 'full-time'],
            ['name' => 'James Wilson',    'phone' => '07700900002', 'postcode' => 'EC1A 1BB', 'job_type' => 'part-time'],
            ['name' => 'Olivia Brown',    'phone' => '07700900003', 'postcode' => 'WC2N 5DU', 'job_type' => 'full-time'],
            ['name' => 'Noah Davies',     'phone' => '07700900004', 'postcode' => 'E1 6RF',   'job_type' => 'contract'],
            ['name' => 'Sophia Jones',    'phone' => '07700900005', 'postcode' => 'N1 9GU',   'job_type' => 'full-time'],
            ['name' => 'Liam Evans',      'phone' => '07700900006', 'postcode' => 'SE1 7PB',  'job_type' => 'part-time'],
            ['name' => 'Amelia Roberts',  'phone' => '07700900007', 'postcode' => 'W1A 0AX',  'job_type' => 'full-time'],
            ['name' => 'Oliver Lewis',    'phone' => '07700900008', 'postcode' => 'NW1 4NP',  'job_type' => 'full-time'],
            ['name' => 'Isabella Walker', 'phone' => '07700900009', 'postcode' => 'B1 1AA',   'job_type' => 'contract'],
            ['name' => 'William Hall',    'phone' => '07700900010', 'postcode' => 'M1 1AE',   'job_type' => 'full-time'],
            ['name' => 'Mia White',       'phone' => '07700900011', 'postcode' => 'LS1 3AB',  'job_type' => 'part-time'],
            ['name' => 'James Harris',    'phone' => '07700900012', 'postcode' => 'BS1 5TT',  'job_type' => 'full-time'],
            ['name' => 'Charlotte Clark', 'phone' => '07700900013', 'postcode' => 'L1 0AB',   'job_type' => 'full-time'],
            ['name' => 'Henry Young',     'phone' => '07700900014', 'postcode' => 'S1 2PP',   'job_type' => 'contract'],
            ['name' => 'Grace King',      'phone' => '07700900015', 'postcode' => 'NG1 1AA',  'job_type' => 'part-time'],
            ['name' => 'Ethan Scott',     'phone' => '07700900016', 'postcode' => 'CV1 2LH',  'job_type' => 'full-time'],
            ['name' => 'Lily Adams',      'phone' => '07700900017', 'postcode' => 'OX1 1BY',  'job_type' => 'full-time'],
            ['name' => 'Oscar Wright',    'phone' => '07700900018', 'postcode' => 'CB2 1TN',  'job_type' => 'part-time'],
            ['name' => 'Poppy Mitchell',  'phone' => '07700900019', 'postcode' => 'RG1 1NH',  'job_type' => 'contract'],
            ['name' => 'Freddie Turner',  'phone' => '07700900020', 'postcode' => 'GL1 1NE',  'job_type' => 'full-time'],
        ];

        $userIds    = array_column($users, 'id');
        $catIds     = array_column($categories, 'id');
        $titleIds   = array_column($titles, 'id');
        $sourceIds  = array_column($sources, 'id');

        foreach ($demo as $i => $d) {
            DB::table('applicants')->insertOrIgnore([
                'applicant_uid'        => Str::uuid(),
                'user_id'              => $userIds[$i % count($userIds)],
                'job_source_id'        => $sourceIds[$i % count($sourceIds)],
                'job_category_id'      => $catIds[$i % count($catIds)],
                'job_title_id'         => $titleIds[$i % count($titleIds)],
                'job_type'             => $d['job_type'],
                'applicant_name'       => $d['name'],
                'applicant_email'      => strtolower(str_replace(' ', '.', $d['name'])) . '@example.com',
                'applicant_postcode'   => $d['postcode'],
                'applicant_phone'      => $d['phone'],
                'applicant_notes'      => 'Demo applicant created by DemoSeeder.',
                'applicant_experience' => 'Sample experience text for demo purposes.',
                'created_at'           => now()->subDays(rand(1, 90)),
                'updated_at'           => now(),
            ]);
        }
    }
}
