<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class DiagnoseAuth extends Command
{
    protected $signature = 'diagnose:auth {email}';
    protected $description = 'Diagnose authentication issues for a user';

    public function handle()
    {
        $email = $this->argument('email');

        // Find all users with this email
        $users = User::where('email', $email)->get();

        $this->info("=== AUTHENTICATION DIAGNOSIS FOR: {$email} ===");
        $this->info("Found {$users->count()} user(s) with this email:");

        foreach ($users as $user) {
            $this->info("User ID: {$user->id}");
            $this->info("  Email: {$user->email}");
            $this->info("  Tenant ID: {$user->tenant_id}");
            $this->info("  Email Verified: " . ($user->email_verified_at ? 'YES' : 'NO'));
            $this->info("  Created: {$user->created_at}");

            // Get tenant info
            if ($user->tenant_id) {
                $tenant = Tenant::find($user->tenant_id);
                if ($tenant) {
                    $this->info("  Tenant: {$tenant->name} ({$tenant->subdomain})");
                    $this->info("  Tenant Status: {$tenant->status}");
                } else {
                    $this->error("  Tenant not found!");
                }
            } else {
                $this->info("  No tenant assigned");
            }

            // Test password
            if ($this->confirm("Test password 'password' for user {$user->id}?", true)) {
                $passwordCorrect = Hash::check('password', $user->password);
                $this->info("  Password 'password' is: " . ($passwordCorrect ? 'CORRECT' : 'INCORRECT'));
            }

            $this->info("");
        }

        // Test tenant context without user
        $this->info("=== TESTING TENANT CONTEXT ===");
        $demoTenant = Tenant::where('subdomain', 'demo')->first();
        if ($demoTenant) {
            $this->info("Demo tenant found:");
            $this->info("  ID: {$demoTenant->id}");
            $this->info("  Name: {$demoTenant->name}");
            $this->info("  Subdomain: {$demoTenant->subdomain}");
            $this->info("  Status: {$demoTenant->status}");

            // Set tenant context and try to find user
            $tenantContext = app(TenantContext::class);
            $tenantContext->set($demoTenant);

            $this->info("Set tenant context, now searching for users...");
            $usersInTenant = User::where('email', $email)->get();
            $this->info("Found {$usersInTenant->count()} user(s) with tenant context set");

            foreach ($usersInTenant as $user) {
                $this->info("  User ID: {$user->id} (Tenant: {$user->tenant_id})");
            }
        } else {
            $this->error("Demo tenant not found!");
        }

        return 0;
    }
}
