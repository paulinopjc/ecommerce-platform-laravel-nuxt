<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // GET /api/v1/categories
    // -----------------------------------------------------------------------

    public function test_index_returns_active_categories(): void
    {
        Category::create(['name' => 'Active A', 'slug' => 'active-a', 'is_active' => true,  'position' => 2]);
        Category::create(['name' => 'Active B', 'slug' => 'active-b', 'is_active' => true,  'position' => 1]);
        Category::create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false, 'position' => 0]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk()
                 ->assertJsonCount(2, 'data');
    }

    public function test_index_orders_by_position(): void
    {
        Category::create(['name' => 'Second', 'slug' => 'second', 'is_active' => true, 'position' => 2]);
        Category::create(['name' => 'First',  'slug' => 'first',  'is_active' => true, 'position' => 1]);

        $response = $this->getJson('/api/v1/categories');

        $names = collect($response->json('data'))->pluck('name')->values();
        $this->assertEquals(['First', 'Second'], $names->toArray());
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/categories
    // -----------------------------------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/categories', ['name' => 'Test', 'slug' => 'test'])
             ->assertUnauthorized();
    }

    public function test_store_requires_admin_or_manager_role(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($customer)
             ->postJson('/api/v1/categories', ['name' => 'Test', 'slug' => 'test'])
             ->assertForbidden();
    }

    public function test_store_creates_category_as_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
                         ->postJson('/api/v1/categories', [
                             'name'     => 'Electronics',
                             'slug'     => 'electronics',
                             'position' => 1,
                             'is_active' => true,
                         ]);

        $response->assertCreated()
                 ->assertJsonPath('data.name', 'Electronics')
                 ->assertJsonPath('data.slug', 'electronics');

        $this->assertDatabaseHas('categories', ['slug' => 'electronics']);
    }

    public function test_store_creates_category_as_manager(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($manager)
             ->postJson('/api/v1/categories', ['name' => 'Clothing', 'slug' => 'clothing'])
             ->assertCreated();
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Category::create(['name' => 'Existing', 'slug' => 'taken']);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
             ->postJson('/api/v1/categories', ['name' => 'New', 'slug' => 'taken'])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['slug']);
    }

    public function test_store_requires_name_and_slug(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
             ->postJson('/api/v1/categories', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['name', 'slug']);
    }

    // -----------------------------------------------------------------------
    // PATCH /api/v1/categories/{category}
    // -----------------------------------------------------------------------

    public function test_update_modifies_category(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->actingAs($admin)
             ->patchJson("/api/v1/categories/{$category->id}", ['name' => 'New Name'])
             ->assertOk()
             ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    public function test_update_rejects_slug_already_taken_by_another_category(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Category::create(['name' => 'Other', 'slug' => 'taken-slug']);
        $category = Category::create(['name' => 'Mine', 'slug' => 'mine']);

        $this->actingAs($admin)
             ->patchJson("/api/v1/categories/{$category->id}", ['slug' => 'taken-slug'])
             ->assertUnprocessable();
    }

    public function test_update_allows_same_slug_on_own_record(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => 'Same', 'slug' => 'same-slug']);

        $this->actingAs($admin)
             ->patchJson("/api/v1/categories/{$category->id}", ['slug' => 'same-slug', 'name' => 'Updated'])
             ->assertOk();
    }

    // -----------------------------------------------------------------------
    // DELETE /api/v1/categories/{category}
    // -----------------------------------------------------------------------

    public function test_destroy_removes_category(): void
    {
        $admin    = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $category = Category::create(['name' => 'Delete Me', 'slug' => 'delete-me']);

        $this->actingAs($admin)
             ->deleteJson("/api/v1/categories/{$category->id}")
             ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_requires_admin_or_manager(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $category = Category::create(['name' => 'Protected', 'slug' => 'protected']);

        $this->actingAs($customer)
             ->deleteJson("/api/v1/categories/{$category->id}")
             ->assertForbidden();
    }
}
