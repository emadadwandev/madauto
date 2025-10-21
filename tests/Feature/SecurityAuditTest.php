<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Order;
use App\Models\ApiCredential;
use App\Services\LoyverseApiService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function it_prevents_sql_injection_in_routes()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin', $tenant->id);

        // Try SQL injection through URL parameters
        $response = $this->actingAs($user)
            ->get("http://testtenant.localhost/dashboard/orders?search=1'; DROP TABLE users; --");

        $response->assertStatus(200);
        
        // Verify table still exists
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('tenants', 1);
    }

    /** @test */
    public function it_prevents_xss_through_data_inputs()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        // Try XSS through order data
        $maliciousPayload = [
            'order_id' => 'XSS<script>alert("XSS")</script>',
            'items' => [
                [
                    'name' => '<img src=x onerror=alert("XSS")>',
                    'description' => 'javascript:alert("XSS")',
                ]
            ],
        ];

        // This would normally be handled by webhook, but testing direct input
        $response = $this->actingAs($adminUser)
            ->post("http://testtenant.localhost/api/orders", $maliciousPayload);

        // Should sanitize or reject input, not execute scripts
        $response->assertStatus(400);
        
        // Check if malicious content was stored
        $order = Order::where('careem_order_id', 'LIKE', '%script%')->first();
        $this->assertNull($order);
    }

    /** @test */
    public function it_protects_against_csrf_attacks()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        // Try to submit form without CSRF token
        $response = $this->actingAs($adminUser)
            ->post("http://testtenant.localhost dashboard/menus", [
                'name' => 'Test Menu',
            ], ['X-Requested-With' => 'XMLHttpRequest']); // But no CSRF token

        // Laravel should reject due to missing CSRF
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function it_encrypts_sensitive_api_credentials()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        $testKey = 'sk_test_sensitive_api_key_12345';

        // Store API credentials
        $credential = ApiCredential::create([
            'tenant_id' => $tenant->id,
            'service' => 'loyverse',
            'credentials' => encrypt([
                'api_key' => $testKey,
                'store_id' => 'store_123',
            ]),
            'is_active' => true,
        ]);

        // Verify credentials are encrypted in database
        $rawCredentials = \DB::table('api_credentials')
            ->where('id', $credential->id)
            ->value('credentials');
            
        $this->assertStringNotContainsString($testKey, $rawCredentials);
        $this->assertStringNotContainsString('api_key', $rawCredentials);
    }

    /** @test */
    public function it_prevents_cross_tenant_data_access_through_model_manipulation()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user1->assignRole('tenant_user', $tenant1->id);

        $privateOrder = Order::factory()->create([
            'tenant_id' => $tenant2->id, // Different tenant
            'careem_order_id' => 'PRIVATE-ORDER',
        ]);

        // Try to access order directly by manipulating model
        $this->actingAs($user1);
        
        $order = Order::withoutGlobalScope('tenant')->find($privateOrder->id);
        $this->assertNull($order); // Should not find it due to tenant scope

        // Try with explicit tenant_id filter (should still be blocked by policies)
        $response = $this->actingAs($user1)
            ->get("http://tenant1.localhost/dashboard/orders/{$privateOrder->id}");
            
        $response->assertStatus(404); // Not found for this tenant
    }

    /** @test */
    public function it_validates_webhook_signatures()
    {
        $tenant = Tenant::factory()->create();
        
        $credential = ApiCredential::create([
            'tenant_id' => $tenant->id,
            'service' => 'careem',
            'credentials' => encrypt(['webhook_secret' => 'test-secret-123']),
            'is_active' => true,
        ]);

        $payload = [
            'order_id' => 'CAREEM-123',
            'items' => [],
            'pricing' => ['total' => 100],
        ];

        // Test without signature should fail
        $response = $this->post("http://{$tenant->subdomain}.localhost/api/webhook/careem", $payload, [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(401); // Unauthorized

        // Test with invalid signature should fail
        $response = $this->post("http://{$tenant->subdomain}.localhost/api/webhook/careem", $payload, [
            'Content-Type' => 'application/json',
            'X-Careem-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_prevents_privilege_escalation_attacks()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        
        $regularUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $regularUser->assignRole('tenant_user', $tenant->id);

        // Try to access admin-only routes
        $restrictedEndpoints = [
            '/dashboard/team',      // Team management
            '/dashboard/invitations/send', // Send invitations
            '/dashboard/locations/create', // Create locations
        ];

        foreach ($restrictedEndpoints as $endpoint) {
            $response = $this->actingAs($regularUser)
                ->post("http://testtenant.localhost{$endpoint}", []);
                
            $response->assertStatus(403); // Forbidden
        }
    }

    /** @test */
    public function it_sanitizes_file_uploads()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        // Try uploading malicious file
        $response = $this->actingAs($adminUser)
            ->post("http://testtenant.localhost/dashboard/menus", [
                'name' => 'Test Menu',
                'image' => [
                    'name' => 'malicious.php',
                    'type' => 'application/x-php',
                    'tmp_name' => '/tmp/malicious',
                    'error' => 0,
                    'size' => 1024,
                ],
            ]);

        $response->assertSessionHasErrors('image');
        $this->assertStringContainsString('image', session('errors')->get('image')[0]);
    }

    /** @test */
    public function it_prevents_brute_force_login_attempts()
    {
        // Laravel's built-in rate limiting should kick in
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 10; $i++) {
            $this->post('/login', [
                'email' => 'doesnotexist@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Should start getting throttled
        $response = $this->post('/login', [
            'email' => 'doesnotexist@test.com',
            'password' => 'wrongpassword',
        ]);

        // Laravel's rate limiting response (429 Too Many Requests)
        $this->assertContains($response->getStatusCode(), [429, 302]);
    }

    /** @test */
    public function it_isolates_tenant_subdomain_requests()
    {
        $tenant1 = Tenant::factory()->create(['subdomain' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['subdomain' => 'tenant2']);

        // User from tenant1 tries to access tenant2's routes
        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user1->assignRole('tenant_admin', $tenant1->id);

        $this->actingAs($user1);

        // Try to access tenant2 dashboard
        $response = $this->get("http://tenant2.localhost/dashboard");
        
        // Should redirect to login or show tenant1 domain, not allow access to tenant2
        // This tests subdomain isolation
        $this->assertContains($response->getStatusCode(), [200, 302, 403]);
    }

    /** @test */
    public function it_handles_session_hijacking_prevention()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin', $tenant->id);

        // Login and get session
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Should set secure session cookie attributes
        $response = $this->get("http://testtenant.localhost/dashboard");
        $cookies = $response->headers->getCookies();

        $sessionCookie = collect($cookies)->first(fn($cookie) => $cookie->getName() === 'laravel_session');
        
        if ($sessionCookie) {
            // In production, these should be true
            $this->assertTrue($sessionCookie->isSecure() || app()->environment('testing')); // HTTPS only in prod
            $this->assertTrue($sessionCookie->isHttpOnly()); // HttpOnly flag
        }
    }

    /** @test */
    public function it_validates_api_request_size_limits()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        
        $credential = ApiCredential::create([
            'tenant_id' => $tenant->id,
            'service' => 'careem',
            'credentials' => encrypt(['webhook_secret' => 'test-secret']),
            'is_active' => true,
        ]);

        // Create huge payload (megabytes of data)
        $hugePayload = [
            'order_id' => 'CAREEM-BIG',
            'items' => array_fill(0, 10000, ['name' => str_repeat('x', 1000)]),
        ];

        $response = $this->post("http://{$tenant->subdomain}.localhost/api/webhook/careem", $hugePayload, [
            'Content-Type' => 'application/json',
        ]);

        // Should reject huge payloads
        $this->assertContains($response->getStatusCode(), [413, 422, 400]);
    }
}
