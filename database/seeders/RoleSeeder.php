<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::SUPER_ADMIN,
                'display_name' => 'Super Admin',
                'description' => 'Platform-wide access to all tenants, subscriptions, and system configuration. Can manage all aspects of the platform.',
            ],
            [
                'name' => Role::TENANT_ADMIN,
                'display_name' => 'Tenant Admin',
                'description' => 'Full access within their tenant. Can manage settings, API credentials, team members, subscriptions, and all tenant data.',
            ],
            [
                'name' => Role::TENANT_USER,
                'display_name' => 'Tenant User',
                'description' => 'Read-only access within their tenant. Can view orders, sync logs, and analytics but cannot modify settings or data.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }

        $this->command->info('Roles seeded successfully!');
    }
}
