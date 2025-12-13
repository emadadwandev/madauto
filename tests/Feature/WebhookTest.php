<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_careem_webhook_requires_api_key()
    {
        $tenant = Tenant::factory()->create([
            'subdomain' => 'test-tenant',
            'careem_api_key' => 'valid-key',
        ]);

        $payload = [
            'order_id' => '123',
            'order' => [
                'id' => '123',
                'items' => [
                    [
                        'product_id' => 'p1',
                        'name' => 'Burger',
                        'quantity' => 1,
                        'price' => 10,
                    ],
                ],
            ],
        ];

        // Test without key
        $response = $this->postJson("/api/webhook/careem/{$tenant->subdomain}", $payload);
        $response->assertStatus(401);
        $this->assertStringContainsString('Invalid or missing x-careem-api-key header', $response->exception->getMessage());

        // Test with invalid key
        $response = $this->postJson("/api/webhook/careem/{$tenant->subdomain}", $payload, [
            'x-careem-api-key' => 'invalid-key',
        ]);
        $response->assertStatus(401);
        $this->assertStringContainsString('Invalid or missing x-careem-api-key header', $response->exception->getMessage());

        // Test with valid key (should fail on signature later, or credentials missing)
        // This proves it passed the API key check
        $response = $this->postJson("/api/webhook/careem/{$tenant->subdomain}", $payload, [
            'x-careem-api-key' => 'valid-key',
        ]);
        $response->assertStatus(401);
        // The message should NOT be about API key
        $this->assertStringNotContainsString('Invalid or missing x-careem-api-key header', $response->exception->getMessage());
    }
}
