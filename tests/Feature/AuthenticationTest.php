<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Invitation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    /** @test */
    public function it_authenticates_tenant_user_successfully()
    {
        // Create tenant and user
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'admin@testtenant.test',
        ]);
        $user->assignRole('tenant_admin', $tenant->id);

        // Test tenant subdomain authentication
        $response = $this->post('/login', [
            'email' => 'admin@testtenant.test',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        
        // Should redirect to dashboard on tenant subdomain
        $response->assertRedirect('http://testtenant.localhost/dashboard');
        
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function it_authenticates_super_admin_successfully()
    {
        // Create super admin (no tenant)
        $superAdmin = User::factory()->create([
            'tenant_id' => null,
            'email' => 'super@admin.test',
        ]);
        $superAdmin->assignRole('super_admin');

        // Test admin subdomain authentication
        $response = $this->post('/login', [
            'email' => 'super@admin.test',
            'password' => 'password',
        ]);

        $response->assertStatus(302);
        
        // Should redirect to super admin dashboard
        $response->assertRedirect('http://admin.localhost/super-admin/dashboard');
        
        $this->assertAuthenticatedAs($superAdmin);
    }

    /** @test */
    public function it_prevents_tenant_user_from_accessing_admin_panel()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_user', $tenant->id);

        // Authenticate as tenant user
        $this->actingAs($user);

        // Try to access super admin panel
        $response = $this->get('http://admin.localhost/super-admin/dashboard');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_super_admin_from_accessing_tenant_dashboard()
    {
        $superAdmin = User::factory()->create(['tenant_id' => null]);
        $superAdmin->assignRole('super_admin');

        // Authenticate as super admin
        $this->actingAs($superAdmin);

        // Try to access tenant dashboard without tenant context
        $response = $this->get('http://testtenant.localhost/dashboard');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function it_handles_invitation_acceptance_flow()
    {
        // Create tenant and invitation
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        $this->actingAs($adminUser);

        $invitation = Invitation::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'newuser@test.com',
            'token' => 'test-token-123',
        ]);

        // Test invitation acceptance page loads
        $response = $this->get('/invitations/test-token-123');
        
        $response->assertStatus(200);
        $response->assertSee('Accept Invitation');
        $response->assertSee('newuser@test.com');
    }

    /** @test */
    public function it_prevents_invalid_invitation_acceptance()
    {
        $response = $this->get('/invitations/invalid-token');
        
        $response->assertStatus(404);
    }

    /** @test */
    public function it_creates_user_from_valid_invitation()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        $invitation = Invitation::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'invitee@test.com',
            'token' => 'valid-token-456',
        ]);

        // Accept invitation with user data
        $response = $this->post('/invitations/valid-token-456/accept', [
            'name' => 'New User',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('http://testtenant.localhost/dashboard');
        
        // Verify user was created
        $newUser = User::where('email', 'invitee@test.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('New User', $newUser->name);
        $this->assertEquals($tenant->id, $newUser->tenant_id);
        $this->assertTrue($newUser->hasRole('tenant_user', $tenant));
        
        // Verify invitation was marked as accepted
        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        
        // Verify user is logged in
        $this->assertAuthenticatedAs($newUser);
    }

    /** @test */
    public function it_prevents_duplicate_email_in_same_tenant()
    {
        $tenant = Tenant::factory()->create();
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $adminUser->assignRole('tenant_admin', $tenant->id);

        $this->actingAs($adminUser);

        // Create existing user
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'existing@test.com',
        ]);

        // Try to invite same email
        $response = $this->post('/dashboard/invitations', [
            'email' => 'existing@test.com',
            'role_id' => 3, // tenant_user role
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('already exists', session('errors')->get('email')[0]);
    }

    /** @test */
    public function it_allows_same_email_in_different_tenants()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Create user in tenant 1
        User::factory()->create([
            'tenant_id' => $tenant1->id,
            'email' => 'shared@test.com',
        ]);

        // Try to create invitation for same email in tenant 2
        $admin2 = User::factory()->create(['tenant_id' => $tenant2->id]);
        $admin2->assignRole('tenant_admin', $tenant2->id);

        $response = $this->actingAs($admin2)
            ->post('/dashboard/invitations', [
                'email' => 'shared@test.com',
                'role_id' => 3,
            ]);

        $response->assertRedirect(); // Should succeed
        $this->assertDatabaseHas('invitations', [
            'tenant_id' => $tenant2->id,
            'email' => 'shared@test.com',
        ]);
    }

    /** @test */
    public function it_protects_tenant_specific_routes()
    {
        $publicResponse = $this->get('/'); // Landing page
        $publicResponse->assertStatus(200);

        $tenantResponse = $this->get('/dashboard'); // Tenant dashboard
        $tenantResponse->assertRedirect('/login');

        $adminResponse = $this->get('/super-admin/dashboard'); // Admin panel
        $adminResponse->assertRedirect('/login');
    }

    /** @test */
    public function it_maintains_tenant_session_across_subdomains()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('tenant_admin', $tenant->id);

        // Login on tenant subdomain
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Verify user is authenticated on same subdomain
        $response = $this->get('http://testtenant.localhost/dashboard');
        $response->assertStatus(200);

        // Should still be authenticated when accessing other tenant pages
        $ordersResponse = $this->get('http://testtenant.localhost/dashboard/orders');
        $ordersResponse->assertStatus(200);
    }

    /** @test */
    public function it_prevents_tenant_data_leakage_though_api()
    {
        $tenant1 = Tenant::factory()->create(['subdomain' => 'tenant1']);
        $tenant2 = Tenant::factory()->create(['subdomain' => 'tenant2']);

        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user1->assignRole('tenant_admin', $tenant1->id);

        $order2 = Order::factory()->create([
            'tenant_id' => $tenant2->id,
            'careem_order_id' => 'CAREEM-T2-123',
        ]);

        // User 1 tries to access order belonging to tenant 2
        $response = $this->actingAs($user1)
            ->get('http://tenant1.localhost/api/orders');

        $response->assertJsonCount(0, 'data'); // Should return empty, not other tenant's data
    }

    /** @test */
    public function it_handles_role_based_authorization_correctly()
    {
        $tenant = Tenant::factory()->create(['subdomain' => 'testtenant']);
        
        $adminUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $regularUser = User::factory()->create(['tenant_id' => $tenant->id]);
        
        $adminUser->assignRole('tenant_admin', $tenant->id);
        $regularUser->assignRole('tenant_user', $tenant->id);

        // Admin can access team management
        $adminResponse = $this->actingAs($adminUser)
            ->get('http://testtenant.localhost/dashboard/team');
        $adminResponse->assertStatus(200);

        // User cannot access team management
        $userResponse = $this->actingAs($regularUser)
            ->get('http://testtenant.localhost/dashboard/team');
        $userResponse->assertStatus(403);

        // But user can access basic dashboard
        $dashboardResponse = $this->actingAs($regularUser)
            ->get('http://testtenant.localhost/dashboard');
        $dashboardResponse->assertStatus(200);
    }
}
