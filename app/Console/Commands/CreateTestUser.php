<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTestUser extends Command
{
    protected $signature = 'test:create-user {email} {name?} {subdomain?}';
    protected $description = 'Create a test user for a tenant';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name') ?? 'Test User';
        $subdomain = $this->argument('subdomain') ?? 'demo';

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (!$tenant) {
            $this->error("Tenant with subdomain '{$subdomain}' not found");
            return 1;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->info("User already exists: {$existingUser->email}");
            $this->info("User ID: {$existingUser->id}");
            $this->info("Tenant ID: {$existingUser->tenant_id}");

            // Ensure email is verified
            if (!$existingUser->email_verified_at) {
                $existingUser->email_verified_at = now();
                $existingUser->save();
                $this->info("✅ Email verified for existing user");
            } else {
                $this->info("✅ Email already verified");
            }

            return 0;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id
        ]);

        $this->info("User created successfully!");
        $this->info("Email: {$user->email}");
        $this->info("Password: password");
        $this->info("Tenant: {$tenant->name} (subdomain: {$tenant->subdomain})");

        return 0;
    }
}
