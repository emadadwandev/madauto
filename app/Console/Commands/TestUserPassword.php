<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestUserPassword extends Command
{
    protected $signature = 'test:password {email} {password}';

    protected $description = 'Test if password matches for a user';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found");

            return 1;
        }

        $this->info('User found:');
        $this->info("ID: {$user->id}");
        $this->info("Email: {$user->email}");
        $this->info("Name: {$user->name}");
        $this->info("Tenant ID: {$user->tenant_id}");
        $this->info('Email verified: '.($user->email_verified_at ? 'YES' : 'NO'));
        $this->info('Password hash: '.substr($user->password, 0, 20).'...');

        $passwordMatches = Hash::check($password, $user->password);
        $this->info('Password matches: '.($passwordMatches ? 'YES' : 'NO'));

        if (! $passwordMatches) {
            $this->error('Password does not match!');
            $this->info("Expected password: {$password}");
            $this->info('Try creating a new password hash:');
            $this->info('New hash: '.Hash::make($password));
        } else {
            $this->info('✅ Password verification successful!');
        }

        return 0;
    }
}
