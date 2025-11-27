<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCareemApiKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:generate-careem-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Careem API keys for tenants that do not have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::whereNull('careem_api_key')->get();

        if ($tenants->isEmpty()) {
            $this->info('All tenants already have Careem API keys.');
            return;
        }

        $this->info("Generating keys for {$tenants->count()} tenants...");

        foreach ($tenants as $tenant) {
            $key = 'ck_' . Str::random(32);
            $tenant->update(['careem_api_key' => $key]);
            $this->info("Generated key for tenant: {$tenant->subdomain}");
        }

        $this->info('Done.');
    }
}
