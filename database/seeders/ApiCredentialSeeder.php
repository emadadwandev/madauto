<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ApiCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Note: API credentials are now tenant-specific and should be set up
     * per-tenant through the tenant dashboard, not via seeder.
     */
    public function run(): void
    {
        // Commented out - API credentials are now tenant-specific
        // Set up credentials through tenant dashboard in production

        $this->command->info('API credentials should be set up per tenant through the dashboard.');
    }
}
