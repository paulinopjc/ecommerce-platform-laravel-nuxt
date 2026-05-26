<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(Customer $customer, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_id'    => $customer->id,
            'order_number'   => 'ORD-' . uniqid(),
            'status'         => Order::STATUS_PENDING_PAYMENT,
            'payment_method' => Order::PAYMENT_COD,
            'total_cents'    => 10000,
        ], $overrides));
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/orders  (admin/manager only)
    // -----------------------------------------------------------------------

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/orders')->assertUnauthorized();
    }

    public function test_index_requires_admin_or_manager_role(): void
    {
        // A Customer token must be rejected on admin-only routes
        $customer = Customer::factory()->create();
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/orders')->assertForbidden();
    }

    public function test_index_returns_paginated_orders_for_admin(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();

        $this->makeOrder($customer);
        $this->makeOrder($customer);

        $response = $this->actingAs($admin)->getJson('/api/v1/orders');

        $response->assertOk()
                 ->assertJsonStructure(['data', 'total', 'current_page']);
    }

    public function test_index_filters_by_status(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();

        $this->makeOrder($customer, ['status' => Order::STATUS_PENDING_PAYMENT]);
        $this->makeOrder($customer, ['status' => Order::STATUS_PAID]);

        $response = $this->actingAs($admin)
                         ->getJson('/api/v1/orders?status=' . Order::STATUS_PAID);

        $response->assertOk();
        $orders = $response->json('data');
        $this->assertCount(1, $orders);
        $this->assertEquals(Order::STATUS_PAID, $orders[0]['status']);
    }

    public function test_index_filters_by_source(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();

        $this->makeOrder($customer, ['source' => Order::SOURCE_WEB]);
        $this->makeOrder($customer, ['source' => Order::SOURCE_SHOPEE]);

        $response = $this->actingAs($admin)
                         ->getJson('/api/v1/orders?source=' . Order::SOURCE_SHOPEE);

        $response->assertOk();
        $orders = $response->json('data');
        $this->assertCount(1, $orders);
        $this->assertEquals(Order::SOURCE_SHOPEE, $orders[0]['source']);
    }

    // -----------------------------------------------------------------------
    // GET /api/v1/orders/{order}
    // -----------------------------------------------------------------------

    public function test_show_allows_customer_to_view_own_order(): void
    {
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/orders/{$order->id}")
             ->assertOk()
             ->assertJsonPath('data.id', $order->id);
    }

    public function test_show_blocks_customer_from_viewing_another_customers_order(): void
    {
        $owner = Customer::factory()->create();
        $other = Customer::factory()->create();
        $order = $this->makeOrder($owner);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/orders/{$order->id}")->assertNotFound();
    }

    public function test_show_allows_manager_to_view_any_order(): void
    {
        $manager  = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer);

        $this->actingAs($manager)
             ->getJson("/api/v1/orders/{$order->id}")
             ->assertOk();
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/orders/{order}/mark-paid
    // -----------------------------------------------------------------------

    public function test_mark_as_paid_requires_admin_or_manager(): void
    {
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, ['payment_method' => Order::PAYMENT_COD]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/orders/{$order->id}/mark-paid")->assertForbidden();
    }

    public function test_mark_as_paid_rejects_non_cod_orders(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, ['payment_method' => Order::PAYMENT_XENDIT]);

        $this->actingAs($admin)
             ->postJson("/api/v1/orders/{$order->id}/mark-paid")
             ->assertStatus(422)
             ->assertJsonPath('message', 'Only COD orders can be marked paid manually');
    }

    public function test_mark_as_paid_rejects_already_paid_order(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, [
            'payment_method' => Order::PAYMENT_COD,
            'status'         => Order::STATUS_PAID,
        ]);

        $this->actingAs($admin)
             ->postJson("/api/v1/orders/{$order->id}/mark-paid")
             ->assertStatus(422)
             ->assertJsonPath('message', 'Order already paid');
    }

    public function test_mark_as_paid_updates_order_status_to_paid(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, [
            'payment_method' => Order::PAYMENT_COD,
            'status'         => Order::STATUS_PENDING_PAYMENT,
        ]);

        Payment::create([
            'order_id'       => $order->id,
            'amount_cents'   => $order->total_cents,
            'currency'       => 'PHP',
            'payment_method' => Order::PAYMENT_COD,
            'status'         => Payment::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
             ->postJson("/api/v1/orders/{$order->id}/mark-paid")
             ->assertOk()
             ->assertJsonPath('data.status', Order::STATUS_PAID);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => Order::STATUS_PAID,
        ]);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/v1/orders/{order}/status
    // -----------------------------------------------------------------------

    public function test_update_status_requires_valid_transition(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, ['status' => Order::STATUS_PENDING_PAYMENT]);

        // Cannot go from pending_payment directly to shipped
        $this->actingAs($admin)
             ->patchJson("/api/v1/orders/{$order->id}/status", ['status' => Order::STATUS_SHIPPED])
             ->assertStatus(422);
    }

    public function test_update_status_allows_valid_transition(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::factory()->create();
        $order    = $this->makeOrder($customer, ['status' => Order::STATUS_PAID]);

        $this->actingAs($admin)
             ->patchJson("/api/v1/orders/{$order->id}/status", [
                 'status' => Order::STATUS_PROCESSING,
             ])
             ->assertOk()
             ->assertJsonPath('data.status', Order::STATUS_PROCESSING);
    }
}
