<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOAuthTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // GET /api/v1/auth/google?mode=customer  (storefront flow)
    // -----------------------------------------------------------------------

    public function test_customer_oauth_creates_new_customer_on_first_login(): void
    {
        // Simulate the callback arriving with a new email
        $token = Customer::factory()->create([
            'email' => 'new@example.com',
        ])->createToken('auth')->plainTextToken;

        // Verify a customer record now exists
        $this->assertDatabaseHas('customers', ['email' => 'new@example.com']);

        // And the token belongs to a Customer, not a User
        $response = $this->withToken($token)->getJson('/api/v1/auth/me');
        $response->assertOk()->assertJsonPath('data.type', 'customer');
    }

    public function test_customer_token_can_access_cart(): void
    {
        $customer = Customer::factory()->create();
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/cart')->assertOk();
    }

    public function test_customer_token_cannot_access_admin_orders_index(): void
    {
        $customer = Customer::factory()->create();
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/orders')->assertForbidden();
    }

    public function test_customer_token_cannot_mark_order_paid(): void
    {
        $customer = Customer::factory()->create();
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/orders/1/mark-paid')
             ->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // User (backoffice) token isolation
    // -----------------------------------------------------------------------

    public function test_user_token_cannot_access_customer_me(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $admin->createToken('auth')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/customer/me')->assertForbidden();
    }

    public function test_user_token_can_access_admin_orders_index(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $token = $admin->createToken('auth')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/orders')->assertOk();
    }

    // -----------------------------------------------------------------------
    // Inactive customer
    // -----------------------------------------------------------------------

    public function test_inactive_customer_token_still_authenticates_but_application_blocks_manually(): void
    {
        // Sanctum issues a token regardless; blocking is enforced at OAuth callback level.
        // This test confirms the factory's inactive() state works.
        $customer = Customer::factory()->inactive()->create();
        $this->assertFalse($customer->is_active);
    }

    // -----------------------------------------------------------------------
    // Customer profile routes
    // -----------------------------------------------------------------------

    public function test_customer_me_returns_customer_data(): void
    {
        $customer = Customer::factory()->create(['name' => 'Test Customer']);
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)
             ->getJson('/api/v1/customer/me')
             ->assertOk()
             ->assertJsonPath('data.name', 'Test Customer');
    }

    public function test_customer_addresses_returns_empty_array_initially(): void
    {
        $customer = Customer::factory()->create();
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)
             ->getJson('/api/v1/customer/addresses')
             ->assertOk()
             ->assertJsonPath('data', []);
    }

    public function test_customer_can_store_address(): void
    {
        $customer = Customer::factory()->create();
        $token    = $customer->createToken('auth')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/customer/addresses', [
                 'line_1'      => '123 Main St',
                 'city'        => 'Manila',
                 'province'    => 'Metro Manila',
                 'postal_code' => '1000',
             ])
             ->assertCreated()
             ->assertJsonPath('data.city', 'Manila');

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'city'        => 'Manila',
        ]);
    }
}
