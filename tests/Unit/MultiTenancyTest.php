<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\Tenant;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function it_isolates_tenant_data_properly()
    {
        // Create two tenants
        $tenant1 = Tenant::factory()->create([
            'name' => 'Restaurant Alpha',
            'subdomain' => 'alpha',
        ]);
        $tenant2 = Tenant::factory()->create([
            'name' => 'Restaurant Beta',
            'subdomain' => 'beta',
        ]);

        // Create orders for each tenant
        $order1 = Order::factory()->create([
            'tenant_id' => $tenant1->id,
            'careem_order_id' => 'CAREEM-ALPHA-1',
            'order_data' => ['total' => 100],
        ]);

        $order2 = Order::factory()->create([
            'tenant_id' => $tenant2->id,
            'careem_order_id' => 'CAREEM-BETA-1',
            'order_data' => ['total' => 200],
        ]);

        // Test: Tenant 1 should only see their own orders
        app(TenantContext::class)->set($tenant1);
        $tenant1Orders = Order::all();
        $this->assertCount(1, $tenant1Orders);
        $this->assertEquals('CAREEM-ALPHA-1', $tenant1Orders->first()->careem_order_id);

        // Test: Tenant 2 should only see their own orders
        app(TenantContext::class)->set($tenant2);
        $tenant2Orders = Order::all();
        $this->assertCount(1, $tenant2Orders);
        $this->assertEquals('CAREEM-BETA-1', $tenant2Orders->first()->careem_order_id);
    }

    /** @test */
    public function it_prevents_cross_tenant_data_access()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => $tenant1->id,
            'careem_order_id' => 'CAREEM-123',
        ]);

        // Simulate tenant 2 trying to access tenant 1's data
        app(TenantContext::class)->set($tenant2);

        // The order should not be found for tenant 2
        $foundOrder = Order::where('careem_order_id', 'CAREEM-123')->first();
        $this->assertNull($foundOrder);

        // Total orders should be 0 for tenant 2
        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function it_sets_tenant_id_automatically_on_creation()
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);

        $order = Order::factory()->create([
            'careem_order_id' => 'CAREEM-456',
            'order_data' => ['total' => 150],
        ]);

        // Verify tenant_id was set automatically
        $this->assertEquals($tenant->id, $order->tenant_id);
        $this->assertEquals('CAREEM-456', $order->fresh()->careem_order_id);
    }

    /** @test */
    public function it_prevents_changing_tenant_id_after_creation()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        app(TenantContext::class)->set($tenant1);

        $order = Order::factory()->create([
            'careem_order_id' => 'CAREEM-789',
        ]);

        // Try to change tenant_id
        $order->tenant_id = $tenant2->id;
        $order->save();

        // The tenant_id should remain unchanged
        $this->assertEquals($tenant1->id, $order->fresh()->tenant_id);
    }

    /** @test */
    public function it_scopes_queries_to_current_tenant()
    {
        // Create multiple tenants with orders
        $tenants = Tenant::factory()->count(3)->create();

        foreach ($tenants as $index => $tenant) {
            Order::factory()->count(2)->create([
                'tenant_id' => $tenant->id,
                'careem_order_id' => "CAREEM-tenant{$index}-order1",
            ]);
        }

        // Test that queries are scoped to current tenant
        app(TenantContext::class)->set($tenants[1]);

        $allOrders = Order::all();
        $pendingOrders = Order::where('status', 'pending')->get();

        // Only tenant 2's orders should be returned
        $this->assertCount(2, $allOrders);
        $this->assertEquals(2, $pendingOrders->count());

        // Verify the orders belong to the correct tenant
        foreach ($allOrders as $order) {
            $this->assertEquals($tenants[1]->id, $order->tenant_id);
        }
    }

    /** @test */
    public function it_handles_without_global_scope_correctly()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        Order::factory()->create([
            'tenant_id' => $tenant1->id,
            'careem_order_id' => 'CAREEM-SUPER1',
        ]);

        Order::factory()->create([
            'tenant_id' => $tenant2->id,
            'careem_order_id' => 'CAREEM-SUPER2',
        ]);

        // Without tenant context, should return all orders
        $allOrders = Order::withoutGlobalScope('tenant')->get();
        $this->assertCount(2, $allOrders);

        // With tenant context, should return only scoped orders
        app(TenantContext::class)->set($tenant1);
        $scopedOrders = Order::all();
        $this->assertCount(1, $scopedOrders);
    }

    /** @test */
    public function it_handles_null_tenant_context_gracefully()
    {
        $tenant = Tenant::factory()->create();

        Order::factory()->create([
            'tenant_id' => $tenant->id,
            'careem_order_id' => 'CAREEM-NULL-TEST',
        ]);

        // Clear tenant context
        app(TenantContext::class)->clear();

        // Should not throw error but return empty result
        $orders = Order::all();
        $this->assertCount(0, $orders);
    }

    /** @test */
    public function it_maintains_tenant_isolation_in_relationships()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create related data across tenants
        $order1 = Order::factory()->create(['tenant_id' => $tenant1->id]);
        $order2 = Order::factory()->create(['tenant_id' => $tenant2->id]);

        // Create Loyverse orders for each
        $loyverseOrder1 = $order1->loyverseOrder()->create([
            'loyverse_receipt_id' => 'LOY-1',
            'tenant_id' => $tenant1->id,
        ]);

        $loyverseOrder2 = $order2->loyverseOrder()->create([
            'loyverse_receipt_id' => 'LOY-2',
            'tenant_id' => $tenant2->id,
        ]);

        // Test relationship isolation
        app(TenantContext::class)->set($tenant1);
        $foundOrder = Order::with('loyverseOrder')->first();
        $this->assertNotNull($foundOrder);
        $this->assertEquals('LOY-1', $foundOrder->loyverseOrder->loyverse_receipt_id);

        app(TenantContext::class)->set($tenant2);
        $foundOrder = Order::with('loyverseOrder')->first();
        $this->assertNotNull($foundOrder);
        $this->assertEquals('LOY-2', $foundOrder->loyverseOrder->loyverse_receipt_id);
    }

    /** @test */
    public function it_handles_tenant_deletion_cascade()
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);

        // Create tenant data
        $order = Order::factory()->create();
        $loyverseOrder = $order->loyverseOrder()->create([
            'loyverse_receipt_id' => 'LOY-DELETE-TEST',
            'tenant_id' => $tenant->id,
        ]);

        // Verify data exists
        $this->assertEquals(1, Order::count());
        $this->assertEquals(1, $tenant->loyverseOrders()->count());

        // Delete tenant
        $tenant->delete();

        // Verify cascade deletion
        $this->assertEquals(0, Order::count());
        $this->assertEquals(0, \App\Models\LoyverseOrder::count());
    }
}
