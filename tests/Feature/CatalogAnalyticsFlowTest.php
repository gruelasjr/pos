<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductMetadataDefinition;
use App\Models\ProductTag;
use App\Models\ProductType;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogAnalyticsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_taxonomy_typed_values_and_photo_upload_are_persisted(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $token = $this->bearerToken($admin);
        $type = ProductType::factory()->create();
        $product = Product::factory()->create(['product_type_id' => $type->id]);

        $tagId = $this->withToken($token)->postJson('/api/v1/product-tags', ['name' => 'Temporada'])->assertOk()->json('data.id');
        $definitionId = $this->withToken($token)->postJson('/api/v1/product-metadata-definitions', [
            'key' => 'talla', 'label' => 'Talla', 'type' => 'select', 'options' => ['S', 'M', 'L'],
        ])->assertOk()->json('data.id');

        $this->withToken($token)->patchJson("/api/v1/products/{$product->id}", [
            'tag_ids' => [$tagId], 'metadata' => [$definitionId => 'M'],
        ])->assertOk()->assertJsonPath('data.tags.0.name', 'Temporada');

        $this->withToken($token)->post("/api/v1/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('producto.webp', 480, 480)->size(600),
        ])->assertOk();

        $this->assertDatabaseHas('product_product_tag', ['product_id' => $product->id, 'product_tag_id' => $tagId, 'tenant_id' => 'tenant-test']);
        $this->assertDatabaseHas('product_metadata_values', ['product_id' => $product->id, 'definition_id' => $definitionId, 'value_text' => 'M']);
        $firstPath = $this->managedPath(Product::findOrFail($product->id)->photo_url);
        Storage::disk('public')->assertExists($firstPath);
        $this->withToken($token)->post("/api/v1/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('reemplazo.png', 480, 480)->size(500),
        ])->assertOk();
        Storage::disk('public')->assertMissing($firstPath);
        $this->withToken($token)->post("/api/v1/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ])->assertUnprocessable();

        $this->withToken($token)->patchJson("/api/v1/products/{$product->id}", [
            'metadata' => [$definitionId => 'XL'],
        ])->assertUnprocessable();
    }

    public function test_cart_customer_pause_discount_clear_and_hierarchical_best_sellers(): void
    {
        Queue::fake();
        $admin = User::factory()->admin()->create();
        $token = $this->bearerToken($admin);
        $warehouse = Warehouse::factory()->create();
        $type = ProductType::factory()->create(['name' => 'Bebidas']);
        $product = Product::factory()->create(['product_type_id' => $type->id, 'sale_price' => 100]);
        Inventory::create(['id' => (string) Str::uuid(), 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'stock' => 20, 'reorder_point' => 2]);
        $customer = Customer::factory()->create();
        $tagA = ProductTag::create(['name' => 'Frío']);
        $tagB = ProductTag::create(['name' => 'Verano']);
        $product->tags()->sync([$tagA->id => ['tenant_id' => 'tenant-test'], $tagB->id => ['tenant_id' => 'tenant-test']]);
        $definition = ProductMetadataDefinition::create(['key' => 'origen', 'label' => 'Origen', 'type' => 'text']);
        $product->metadataValues()->create(['definition_id' => $definition->id, 'value_text' => 'México']);

        $cartId = $this->withToken($token)->postJson('/api/v1/carts', ['warehouse_id' => $warehouse->id])->assertOk()->json('data.id');
        $this->withToken($token)->postJson("/api/v1/carts/{$cartId}/items", ['product_id' => $product->id, 'quantity' => 3])->assertOk();
        $this->withToken($token)->patchJson("/api/v1/carts/{$cartId}", ['customer_id' => $customer->id, 'discount_total' => 15, 'status' => 'paused'])
            ->assertOk()->assertJsonPath('data.customer.id', $customer->id)->assertJsonPath('data.total_net', '285.00');
        $this->withToken($token)->patchJson("/api/v1/carts/{$cartId}", ['status' => 'active'])->assertOk();

        $this->withToken($token)->withHeader('X-Idempotency-Key', 'analytics-checkout')->postJson("/api/v1/carts/{$cartId}/checkout", [
            'payment_method' => 'cash', 'payment_details' => ['received' => 300],
        ])->assertOk();

        $report = $this->withToken($token)->getJson('/api/v1/reports/best-sellers?group_by%5B%5D=tag&group_by%5B%5D=metadata%3Aorigen')
            ->assertOk()->assertJsonPath('data.summary.units', 3)->assertJsonPath('data.non_additive_tag_totals', true);
        $this->assertCount(2, $report->json('data.tree'));
        $this->withToken($token)->getJson('/api/v1/reports/best-sellers?group_by[]=tag&group_by[]=tag')->assertUnprocessable();
        $this->withToken($token)->get('/api/v1/reports/export?report=best-sellers&format=csv&group_by[]=tag')
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->withToken($token)->get('/api/v1/reports/export?report=overview&format=pdf')
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $clearCart = $this->withToken($token)->postJson('/api/v1/carts', ['warehouse_id' => $warehouse->id])->assertOk()->json('data.id');
        $this->withToken($token)->postJson("/api/v1/carts/{$clearCart}/items", ['product_id' => $product->id, 'quantity' => 1])->assertOk();
        $this->withToken($token)->deleteJson("/api/v1/carts/{$clearCart}/items")->assertOk()
            ->assertJsonPath('data.total_net', '0.00')->assertJsonCount(0, 'data.items');
    }

    public function test_taxonomy_is_isolated_by_tenant(): void
    {
        ProductTag::create(['name' => 'Solo tenant A']);
        $this->useTenant('tenant-other');
        ProductTag::create(['name' => 'Solo tenant B']);
        $this->useTenant('tenant-test');

        $this->assertSame(['Solo tenant A'], ProductTag::query()->pluck('name')->all());
    }

    private function bearerToken(User $user): string
    {
        return \Tests\Support\FakeCaronteAuthentication::tokenFor($user);
    }

    private function managedPath(string $url): string
    {
        return 'products/' . explode('/storage/products/', $url, 2)[1];
    }
}
