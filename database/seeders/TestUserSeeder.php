<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get roles
        $superAdminRole = Role::where('name', Role::SUPER_ADMIN)->first();
        $tenantAdminRole = Role::where('name', Role::TENANT_ADMIN)->first();
        $tenantUserRole = Role::where('name', Role::TENANT_USER)->first();

        if (!$superAdminRole || !$tenantAdminRole || !$tenantUserRole) {
            $this->command->error('Roles not found! Please run RoleSeeder first.');
            return;
        }

        // ========================================
        // 1. CREATE SUPER ADMIN USER
        // ========================================
        $this->command->info('Creating Super Admin user...');

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@saas.test'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@saas.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => null, // Super admin has no tenant
            ]
        );

        // Assign super admin role
        if (!$superAdmin->hasRole(Role::SUPER_ADMIN)) {
            $superAdmin->roles()->attach($superAdminRole->id, [
                'tenant_id' => null,
            ]);
        }

        $this->command->info('✓ Super Admin created: admin@saas.test / password');

        // ========================================
        // 2. CREATE TEST TENANT
        // ========================================
        $this->command->info('Creating test tenant...');

        $tenant = Tenant::updateOrCreate(
            ['subdomain' => 'demo'],
            [
                'name' => 'Demo Restaurant',
                'subdomain' => 'demo',
                'domain' => null,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14),
                'onboarding_completed_at' => now(),
                'settings' => [
                    'timezone' => 'UTC',
                    'email_notifications' => true,
                    'notify_failed_orders' => true,
                    'notify_usage_limit' => true,
                ],
            ]
        );

        $this->command->info('✓ Tenant created: demo.yourapp.test');

        // ========================================
        // 3. CREATE TENANT ADMIN USER
        // ========================================
        $this->command->info('Creating Tenant Admin user...');

        $tenantAdmin = User::updateOrCreate(
            ['email' => 'admin@demo.test'],
            [
                'name' => 'Demo Admin',
                'email' => 'admin@demo.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ]
        );

        // Assign tenant admin role
        if (!$tenantAdmin->hasRole(Role::TENANT_ADMIN, $tenant->id)) {
            $tenantAdmin->roles()->attach($tenantAdminRole->id, [
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->command->info('✓ Tenant Admin created: admin@demo.test / password');

        // ========================================
        // 4. CREATE TENANT USER (Read-only)
        // ========================================
        $this->command->info('Creating Tenant User...');

        $tenantUser = User::updateOrCreate(
            ['email' => 'user@demo.test'],
            [
                'name' => 'Demo User',
                'email' => 'user@demo.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
            ]
        );

        // Assign tenant user role
        if (!$tenantUser->hasRole(Role::TENANT_USER, $tenant->id)) {
            $tenantUser->roles()->attach($tenantUserRole->id, [
                'tenant_id' => $tenant->id,
            ]);
        }

        $this->command->info('✓ Tenant User created: user@demo.test / password');

        // ========================================
        // 5. CREATE SUBSCRIPTION FOR TENANT
        // ========================================
        $this->command->info('Creating subscription...');

        // Get Business plan
        $businessPlan = SubscriptionPlan::where('slug', 'business')->first();

        if (!$businessPlan) {
            $this->command->error('Business plan not found! Please run SubscriptionPlanSeeder first.');
            return;
        }

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $businessPlan->id,
                'stripe_subscription_id' => null, // No Stripe integration yet
                'status' => 'trialing', // On trial
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'cancel_at_period_end' => false,
                'cancelled_at' => null,
            ]
        );

        $this->command->info('✓ Subscription created: Business Plan (Trial)');

        // ========================================
        // SUMMARY
        // ========================================
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('✓ Test users seeded successfully!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('SUPER ADMIN:');
        $this->command->info('  Email:    admin@saas.test');
        $this->command->info('  Password: password');
        $this->command->info('  Access:   http://admin.yourapp.test (or admin.localhost)');
        $this->command->info('');
        $this->command->info('TENANT ADMIN (Demo Restaurant):');
        $this->command->info('  Email:    admin@demo.test');
        $this->command->info('  Password: password');
        $this->command->info('  Access:   http://demo.yourapp.test (or demo.localhost)');
        $this->command->info('  Tenant:   demo');
        $this->command->info('  Plan:     Business (Trial - 14 days)');
        $this->command->info('');
        $this->command->info('TENANT USER (Demo Restaurant):');
        $this->command->info('  Email:    user@demo.test');
        $this->command->info('  Password: password');
        $this->command->info('  Access:   http://demo.yourapp.test (or demo.localhost)');
        $this->command->info('  Role:     Read-only');
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('NOTE: Update your hosts file for subdomain access:');
        $this->command->info('  127.0.0.1 yourapp.test');
        $this->command->info('  127.0.0.1 admin.yourapp.test');
        $this->command->info('  127.0.0.1 demo.yourapp.test');
        $this->command->info('========================================');
    }
}
