<?php

namespace App\Console\Commands;

use App\Models\ApiCredential;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SetCareemCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'careem:set-credentials {tenant_id : The ID of the tenant} {client_id : The Careem Client ID} {client_secret : The Careem Client Secret}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Careem API credentials for a tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $clientId = $this->argument('client_id');
        $clientSecret = $this->argument('client_secret');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        // Create/Update Client ID
        ApiCredential::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'service' => 'careem_catalog',
                'credential_type' => 'client_id',
            ],
            [
                'credential_value' => $clientId,
                'is_active' => true,
            ]
        );

        // Create/Update Client Secret
        ApiCredential::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'service' => 'careem_catalog',
                'credential_type' => 'client_secret',
            ],
            [
                'credential_value' => $clientSecret,
                'is_active' => true,
            ]
        );

        $this->info("Careem credentials set successfully for tenant {$tenantId}.");

        return 0;
    }
}
