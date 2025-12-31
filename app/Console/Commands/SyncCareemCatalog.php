<?php

namespace App\Console\Commands;

use App\Models\CareemBranch;
use App\Models\CareemCatalogItem;
use App\Models\Tenant;
use App\Services\CareemApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCareemCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'careem:sync-catalog
                            {--tenant= : Tenant subdomain}
                            {--catalog-id= : Specific catalog ID to sync}
                            {--all : Sync for all tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[DEPRECATED] Careem API does not support fetching catalogs. Items are saved automatically when pushing catalogs via submitCatalog().';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                $this->syncTenantCatalog($tenant);
            }

            return Command::SUCCESS;
        }

        $tenantSubdomain = $this->option('tenant');

        if (!$tenantSubdomain) {
            $this->error('Please provide --tenant=<subdomain> or use --all');
            return Command::FAILURE;
        }

        $tenant = Tenant::where('subdomain', $tenantSubdomain)->first();

        if (!$tenant) {
            $this->error("Tenant not found: {$tenantSubdomain}");
            return Command::FAILURE;
        }

        $this->syncTenantCatalog($tenant);

        return Command::SUCCESS;
    }

    protected function syncTenantCatalog(Tenant $tenant): void
    {
        $this->info("Syncing catalog for tenant: {$tenant->name} ({$tenant->subdomain})");

        // Set tenant context
        app()->instance('tenant', $tenant);

        // Get branches for this tenant
        $branches = CareemBranch::where('tenant_id', $tenant->id)
            ->where('pos_integration_enabled', true)
            ->get();

        if ($branches->isEmpty()) {
            $this->warn("  No active branches found for {$tenant->name}");
            return;
        }

        foreach ($branches as $branch) {
            $this->syncBranchCatalog($tenant, $branch);
        }
    }

    protected function syncBranchCatalog(Tenant $tenant, CareemBranch $branch): void
    {
        $catalogId = $this->option('catalog-id') ?? "catalog_{$branch->careem_branch_id}";

        $this->line("  Fetching catalog: {$catalogId} for branch: {$branch->name}");

        try {
            // Instantiate API service with tenant context
            $careemApi = new CareemApiService($tenant->id);

            $catalogData = $careemApi->getCatalogItems(
                $catalogId,
                $branch->careem_brand_id,
                $branch->careem_branch_id
            );

            if (empty($catalogData['items'])) {
                $this->warn("    No items found in catalog");
                return;
            }

            $itemsCount = count($catalogData['items']);
            $this->info("    Found {$itemsCount} items");

            $bar = $this->output->createProgressBar($itemsCount);
            $bar->start();

            $synced = 0;
            $errors = 0;

            DB::beginTransaction();

            foreach ($catalogData['items'] as $item) {
                try {
                    CareemCatalogItem::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'careem_item_id' => $item['item_id'] ?? $item['id'],
                        ],
                        [
                            'careem_catalog_id' => $catalogId,
                            'name' => $item['title'] ?? $item['name'] ?? 'Unknown',
                            'description' => $item['description'] ?? null,
                            'sku' => $item['sku'] ?? null,
                            'price' => $item['price'] ?? 0,
                            'currency' => $item['currency'] ?? 'AED',
                            'category_id' => $item['category_id'] ?? null,
                            'is_available' => $item['available'] ?? $item['is_available'] ?? true,
                            'is_active' => $item['is_active'] ?? true,
                            'image_url' => $item['image_url'] ?? null,
                            'modifier_group_ids' => $item['group_ids'] ?? $item['modifier_group_ids'] ?? null,
                            'external_id' => $item['external_id'] ?? null,
                            'raw_data' => $item,
                        ]
                    );

                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to sync catalog item', [
                        'item_id' => $item['item_id'] ?? $item['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            DB::commit();

            $bar->finish();
            $this->newLine();
            $this->info("    ✓ Synced: {$synced} items");

            if ($errors > 0) {
                $this->warn("    ✗ Errors: {$errors} items");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("    Failed to sync catalog: {$e->getMessage()}");
            Log::error('Catalog sync failed', [
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'catalog_id' => $catalogId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
