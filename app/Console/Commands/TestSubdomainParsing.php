<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSubdomainParsing extends Command
{
    protected $signature = 'test:subdomain {host}';

    protected $description = 'Test subdomain parsing logic';

    public function handle()
    {
        $host = $this->argument('host');

        // Remove port number if present (updated logic)
        $hostWithoutPort = explode(':', $host)[0];
        $appDomain = config('app.domain', 'localhost');

        $this->info("Testing host: {$host}");
        $this->info("Host without port: {$hostWithoutPort}");
        $this->info("App domain: {$appDomain}");

        // Updated logic from IdentifyTenant
        $subdomain = str_replace('.'.$appDomain, '', $hostWithoutPort);

        $this->info("Updated subdomain extraction result: '{$subdomain}'");

        // If subdomain equals host, no subdomain was found
        if ($subdomain === $hostWithoutPort || $subdomain === $appDomain) {
            $this->info('No subdomain detected');

            return;
        }

        $this->info("Detected subdomain: '{$subdomain}'");

        // Check for skip conditions
        if (in_array($subdomain, ['www', 'admin', null])) {
            $this->info('Subdomain should be skipped');
        } else {
            $this->info('Subdomain should be processed for tenant lookup');
        }
    }
}
