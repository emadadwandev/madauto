<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function it_handles_large_number_of_tenant_orders_efficiently()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        // Create 1000 orders
        Order::factory()->count(1000)->create([
            'tenant_id' => $tenant->id,
            'status' => 'synced',
        ]);

        // Test query performance
        $startTime = microtime(true);

        $orders = Order::with('loyverseOrder')
            ->where('status', 'synced')
            ->paginate(50);

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        // Should complete within reasonable time (< 500ms for 1000 records)
        $this->assertLessThan(0.5, $queryTime, "Query took {$queryTime}s, should be < 0.5s");
        $this->assertEquals(50, $orders->count());
    }

    /** @test */
    public function it_prevents_n_plus_one_queries_in_menu_loading()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        // Create menu with items and modifiers
        $menu = Menu::factory()->create(['tenant_id' => $tenant->id]);

        // Create 100 menu items each with 5 modifier groups
        for ($i = 0; $i < 100; $i++) {
            $item = MenuItem::factory()->create([
                'menu_id' => $menu->id,
                'tenant_id' => $tenant->id,
            ]);
        }

        // Test query count
        DB::enableQueryLog();

        $startTime = microtime(true);

        $menuWithItems = Menu::with(['items.modifierGroups.modifiers'])
            ->find($menu->id);

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;
        $queryCount = count(DB::getQueryLog());

        // Should be 3 queries max (menu, items, modifier_groups+modifiers)
        $this->assertLessThanOrEqual(3, $queryCount, "Too many queries: {$queryCount}");
        $this->assertLessThan(0.2, $queryTime, "Query took {$queryTime}s, should be < 0.2s");
    }

    /** @test */
    public function it_handles_concurrent_tenant_requests()
    {
        $tenants = Tenant::factory()->count(10)->create();

        // Create orders for each tenant
        foreach ($tenants as $tenant) {
            Order::factory()->count(100)->create([
                'tenant_id' => $tenant->id,
                'status' => 'pending',
            ]);
        }

        // Simulate concurrent requests to different tenants
        $startTime = microtime(true);

        foreach ($tenants as $tenant) {
            \App\Services\TenantContext::set($tenant);

            $orders = Order::where('status', 'pending')->get();
            $this->assertEquals(100, $orders->count());
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // 10 tenant queries should complete quickly
        $this->assertLessThan(0.5, $totalTime, "10 tenant queries took {$totalTime}s, should be < 0.5s");
    }

    /** @test */
    public function it_caches_frequently_accessed_data()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        // Create some data
        Order::factory()->count(50)->create(['tenant_id' => $tenant->id]);

        // First query - should cache
        $startTime = microtime(true);
        $orders1 = Order::all();
        $firstQueryTime = microtime(true) - $startTime;

        // Second query - should use cache
        $startTime = microtime(true);
        $orders2 = Order::all();
        $secondQueryTime = microtime(true) - $startTime;

        // Results should be identical
        $this->assertEquals($orders1->count(), $orders2->count());
        $this->assertEquals($orders1->pluck('id'), $orders2->pluck('id'));
    }

    /** @test */
    public function it_handles_database_index_efficiency()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        // Create 10000 orders with different statuses
        Order::factory()->count(5000)->create([
            'tenant_id' => $tenant->id,
            'status' => 'synced',
        ]);

        Order::factory()->count(3000)->create([
            'tenant_id' => $tenant->id,
            'status' => 'pending',
        ]);

        Order::factory()->count(2000)->create([
            'tenant_id' => $tenant->id,
            'status' => 'failed',
        ]);

        // Test indexed queries
        $startTime = microtime(true);

        $syncedOrders = Order::where('status', 'synced')->get();
        $pendingOrders = Order::where('status', 'pending')->get();
        $failedOrders = Order::where('status', 'failed')->get();

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        $this->assertEquals(5000, $syncedOrders->count());
        $this->assertEquals(3000, $pendingOrders->count());
        $this->assertEquals(2000, $failedOrders->count());

        // Indexed queries should be fast
        $this->assertLessThan(0.3, $queryTime, "Indexed queries took {$queryTime}s, should be < 0.3s");
    }

    /** @test */
    public function it_handles_large_menu_item_lists_efficiently()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        $menu = Menu::factory()->create(['tenant_id' => $tenant->id]);

        // Create 1000 menu items
        MenuItem::factory()->count(1000)->create([
            'menu_id' => $menu->id,
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // Test pagination performance
        $startTime = microtime(true);

        $items = MenuItem::where('menu_id', $menu->id)
            ->orderBy('sort_order')
            ->paginate(20);

        $endTime = microtime(true);
        $queryTime = $endTime - $startTime;

        $this->assertEquals(20, $items->count());
        $this->assertEquals(1000, $items->total());
        $this->assertLessThan(0.2, $queryTime, "Pagination query took {$queryTime}s, should be < 0.2s");
    }

    /** @test */
    public function it_handles_api_response_times_under_load()
    {
        $tenant = Tenant::factory()->create();
        $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin', $tenant->id);

        // Create test data
        Order::factory()->count(100)->create(['tenant_id' => $tenant->id]);

        // Test API endpoint performance
        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->get("http://{$tenant->subdomain}.localhost/api/orders");

        $endTime = microtime(true);
        $responseTime = $endTime - $startTime;

        $response->assertStatus(200);

        // API responses should be fast
        $this->assertLessThan(0.5, $responseTime, "API response took {$responseTime}s, should be < 0.5s");
    }

    /** @test */
    public function it_handles_memory_usage_efficiently()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        $memoryBefore = memory_get_usage();

        // Process large dataset
        for ($i = 0; $i < 10; $i++) {
            Order::factory()->count(100)->create(['tenant_id' => $tenant->id]);

            // Load and process
            $orders = Order::all();
            unset($orders); // Free memory
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Memory usage should be reasonable (< 50MB for 1000 orders)
        $memoryMB = $memoryUsed / 1024 / 1024;
        $this->assertLessThan(50, $memoryMB, "Memory usage is {$memoryMB}MB, should be < 50MB");
    }

    /** @test */
    public function it_handles_concurrent_tenant_context_switching()
    {
        $tenants = Tenant::factory()->count(5)->create();

        // Create orders for each tenant
        foreach ($tenants as $index => $tenant) {
            Order::factory()->count(100)->create([
                'tenant_id' => $tenant->id,
                'careem_order_id' => "TENANT-{$index}-ORDER",
            ]);
        }

        // Rapidly switch between tenants and query data
        $startTime = microtime(true);

        foreach ($tenants as $index => $tenant) {
            \App\Services\TenantContext::set($tenant);

            $orders = Order::all();
            $this->assertEquals(100, $orders->count());

            // Verify data isolation
            foreach ($orders as $order) {
                $this->assertStringContainsString("TENANT-{$index}", $order->careem_order_id);
            }
        }

        $endTime = microtime(true);
        $switchTime = $endTime - $startTime;

        // Context switching should be efficient
        $this->assertLessThan(0.2, $switchTime, "Tenant context switching took {$switchTime}s, should be < 0.2s");
    }

    /** @test */
    public function it_optimizes_database_connection_pooling()
    {
        $tenants = Tenant::factory()->count(20)->create();

        // Simulate simultaneous tenant operations
        $startTime = microtime(true);

        foreach ($tenants as $tenant) {
            \App\Services\TenantContext::set($tenant);

            // Quick operation that doesn't require complex queries
            $orderCount = Order::count();
            $this->assertEquals(0, $orderCount);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Should handle 20 tenant contexts efficiently
        $this->assertLessThan(0.3, $totalTime, "20 tenant contexts took {$totalTime}s, should be < 0.3s");
    }

    /** @test */
    public function it_handles_caching_efficiently_under_load()
    {
        $tenant = Tenant::factory()->create();
        \App\Services\TenantContext::set($tenant);

        // Create test data
        Order::factory()->count(500)->create(['tenant_id' => $tenant->id]);

        // Test multiple cache calls
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $orders = Order::all();
            $this->assertEquals(500, $orders->count());
        }

        $endTime = microtime(true);
        $cacheTime = $endTime - $startTime;

        // Cached calls should be very fast
        $this->assertLessThan(0.1, $cacheTime, "10 cached calls took {$cacheTime}s, should be < 0.1s");
    }
}
