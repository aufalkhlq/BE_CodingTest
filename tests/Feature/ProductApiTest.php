<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_the_static_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('access_token', config('services.product_api.token'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'incorrect',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_read_endpoints_require_the_static_bearer_token(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/api/products')->assertUnauthorized();
        $this->getJson("/api/products/{$product->id}")->assertUnauthorized();
        $this->getJson('/api/products/stats')->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/products')
            ->assertUnauthorized();
    }

    public function test_index_searches_by_name_or_category_filters_status_and_paginates(): void
    {
        Product::factory()->create([
            'name' => 'Laptop untuk kantor',
            'category' => 'Elektronik',
            'status' => ProductStatus::Available,
        ]);
        Product::factory()->create([
            'name' => 'Laptop gaming',
            'category' => 'Elektronik',
            'status' => ProductStatus::OutOfStock,
        ]);
        Product::factory()->create([
            'name' => 'Laptop bisnis',
            'category' => 'Elektronik',
            'status' => ProductStatus::Available,
        ]);
        Product::factory()->create([
            'name' => 'Meja kerja',
            'category' => 'Furnitur',
            'status' => ProductStatus::Available,
        ]);
        Product::factory()->create([
            'name' => 'Headphone',
            'category' => 'Audio',
            'status' => ProductStatus::Available,
        ]);

        $this->withStaticReadToken()
            ->getJson('/api/products?search=Laptop&status=available&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laptop untuk kantor')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/products?search=Laptop&status=available&per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Laptop bisnis');

        $this->getJson('/api/products?search=Audio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'Audio');
    }

    public function test_index_treats_search_wildcards_as_literal_characters(): void
    {
        Product::factory()->create(['name' => '100% Cotton']);
        Product::factory()->create(['name' => '100X Cotton']);
        Product::factory()->create(['name' => 'Model_A']);
        Product::factory()->create(['name' => 'ModelXA']);

        $this->withStaticReadToken()
            ->getJson('/api/products?search=100%25')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', '100% Cotton');

        $this->getJson('/api/products?search=Model%5FA')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Model_A');
    }

    public function test_index_rejects_invalid_filter_values(): void
    {
        $this->withStaticReadToken()
            ->getJson('/api/products?status=unknown&per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'per_page']);
    }

    public function test_store_creates_a_product_for_an_authorized_user(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/products', $this->validProduct())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Kamera Digital')
            ->assertJsonPath('data.status', ProductStatus::Available->value);

        $this->assertDatabaseHas('products', [
            'name' => 'Kamera Digital',
            'category' => 'Elektronik',
            'status' => ProductStatus::Available->value,
        ]);
    }

    public function test_store_rejects_missing_fields_and_invalid_status(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/products', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'category', 'status'])
            ->assertJsonPath('errors.name.0', 'The name field is required.');

        $this->postJson('/api/products', $this->validProduct([
            'status' => 'not-a-status',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_show_returns_the_requested_product(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kamera Digital',
            'status' => ProductStatus::Available,
        ]);

        $this->withStaticReadToken()
            ->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Kamera Digital')
            ->assertJsonPath('data.status', ProductStatus::Available->value);
    }

    public function test_put_updates_all_product_fields_and_requires_each_field(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/products/{$product->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'description', 'price', 'category', 'status']);

        $updatedProduct = [
            'name' => 'Kamera Mirrorless',
            'description' => 'Kamera dengan lensa yang dapat diganti.',
            'price' => 4500000,
            'category' => 'Fotografi',
            'status' => ProductStatus::OutOfStock->value,
        ];

        $this->putJson("/api/products/{$product->id}", $updatedProduct)
            ->assertOk()
            ->assertJsonPath('data.name', 'Kamera Mirrorless')
            ->assertJsonPath('data.status', ProductStatus::OutOfStock->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Kamera Mirrorless',
            'price' => '4500000.00',
            'status' => ProductStatus::OutOfStock->value,
        ]);
    }

    public function test_patch_updates_only_the_provided_fields(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kamera Digital',
            'status' => ProductStatus::Available,
        ]);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson("/api/products/{$product->id}", [
                'status' => ProductStatus::OutOfStock->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Kamera Digital')
            ->assertJsonPath('data.status', ProductStatus::OutOfStock->value);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Kamera Digital',
            'status' => ProductStatus::OutOfStock->value,
        ]);
    }

    public function test_destroy_deletes_a_product_for_an_authorized_user(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_write_routes_reject_requests_without_sanctum_authentication(): void
    {
        $product = Product::factory()->create();

        $this->postJson('/api/products', $this->validProduct())->assertUnauthorized();
        $this->putJson("/api/products/{$product->id}", $this->validProduct())->assertUnauthorized();
        $this->patchJson("/api/products/{$product->id}", ['name' => 'Baru'])->assertUnauthorized();
        $this->deleteJson("/api/products/{$product->id}")->assertUnauthorized();
    }

    public function test_write_routes_reject_requests_when_policy_denies_permission(): void
    {
        $product = Product::factory()->create();
        $user = User::factory()->create();

        \Illuminate\Support\Facades\Gate::before(fn () => false);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/products', $this->validProduct())
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/products/{$product->id}", $this->validProduct())
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/products/{$product->id}", ['name' => 'Baru'])
            ->assertForbidden();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/products/{$product->id}")
            ->assertForbidden();
    }

    public function test_product_statistics_count_each_status(): void
    {
        Product::factory()->count(2)->create(['status' => ProductStatus::Available]);
        Product::factory()->create(['status' => ProductStatus::OutOfStock]);

        $this->withStaticReadToken()
            ->getJson('/api/products/stats')
            ->assertOk()
            ->assertExactJson([
                'total' => 3,
                ProductStatus::Available->value => 2,
                ProductStatus::OutOfStock->value => 1,
            ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProduct(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Kamera Digital',
            'description' => 'Kamera untuk kebutuhan sehari-hari.',
            'price' => 2500000,
            'category' => 'Elektronik',
            'status' => ProductStatus::Available->value,
        ], $overrides);
    }

    private function withStaticReadToken(): static
    {
        return $this->withHeader(
            'Authorization',
            'Bearer '.config('services.product_api.token'),
        );
    }
}
