<?php

namespace Tests\Feature;

use App\Models\ProductMetadataDefinition;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuMetadataRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_range_generates_composite_sku_and_product_inherits_metadata(): void
    {
        $token = \Tests\Support\FakeCaronteAuthentication::tokenFor(User::factory()->admin()->create());
        $origin = ProductMetadataDefinition::create(['key' => 'origen', 'label' => 'Origen', 'type' => 'select', 'options' => ['México']]);
        $brand = ProductMetadataDefinition::create(['key' => 'marca', 'label' => 'Marca', 'type' => 'text']);
        $mx = $this->withToken($token)->postJson("/api/v1/product-metadata-definitions/{$origin->id}/coded-values", ['value' => 'México', 'code' => 'MX'])->assertOk()->json('data.id');
        $nk = $this->withToken($token)->postJson("/api/v1/product-metadata-definitions/{$brand->id}/coded-values", ['value' => 'Nike', 'code' => 'NK'])->assertOk()->json('data.id');

        $range = $this->withToken($token)->postJson('/api/v1/sku-ranges', [
            'segments' => [
                ['definition_id' => $origin->id, 'coded_value_id' => $mx],
                ['definition_id' => $brand->id, 'coded_value_id' => $nk],
            ],
            'from' => 1, 'to' => 100, 'active' => true,
        ])->assertOk()->assertJsonPath('data.composed_prefix', 'MX-NK')->assertJsonPath('data.example_sku', 'MX-NK-000001')->json('data');

        $sku = $this->withToken($token)->postJson('/api/v1/skus/reserve', ['quantity' => 1, 'prefix' => 'MX-NK'])
            ->assertOk()->assertJsonPath('data.skus.0', 'MX-NK-000001')->json('data.skus.0');

        $type = ProductType::factory()->create();
        $productId = $this->withToken($token)->postJson('/api/v1/products', [
            'sku' => $sku,
            'short_description' => 'Tenis Nike',
            'purchase_price' => 100,
            'sale_price' => 150,
            'entry_date' => now()->toDateString(),
            'product_type_id' => $type->id,
            'metadata' => [$origin->id => 'México'],
        ])->assertOk()->json('data.id');

        $this->assertDatabaseHas('product_metadata_values', ['product_id' => $productId, 'definition_id' => $origin->id, 'value_text' => 'México']);
        $this->assertDatabaseHas('product_metadata_values', ['product_id' => $productId, 'definition_id' => $brand->id, 'value_text' => 'Nike']);

        $this->withToken($token)->patchJson("/api/v1/sku-ranges/{$range['id']}", [
            'segments' => [['definition_id' => $brand->id, 'coded_value_id' => $nk]],
            'from' => 1, 'to' => 100, 'active' => true,
        ])->assertUnprocessable();
    }

    public function test_manual_matching_sku_rejects_conflicting_metadata_and_ranges_cannot_overlap(): void
    {
        $token = \Tests\Support\FakeCaronteAuthentication::tokenFor(User::factory()->admin()->create());
        $definition = ProductMetadataDefinition::create(['key' => 'origen', 'label' => 'Origen', 'type' => 'text']);
        $coded = $this->withToken($token)->postJson("/api/v1/product-metadata-definitions/{$definition->id}/coded-values", ['value' => 'México', 'code' => 'MX'])->assertOk()->json('data.id');
        $payload = ['segments' => [['definition_id' => $definition->id, 'coded_value_id' => $coded]], 'from' => 1, 'to' => 100, 'active' => true];
        $this->withToken($token)->postJson('/api/v1/sku-ranges', $payload)->assertOk();
        $this->withToken($token)->postJson('/api/v1/sku-ranges', [...$payload, 'from' => 50, 'to' => 150])->assertUnprocessable();

        $type = ProductType::factory()->create();
        $this->withToken($token)->postJson('/api/v1/products', [
            'sku' => 'MX-000042', 'short_description' => 'Producto manual', 'purchase_price' => 10,
            'sale_price' => 20, 'entry_date' => now()->toDateString(), 'product_type_id' => $type->id,
            'metadata' => [$definition->id => 'Canadá'],
        ])->assertUnprocessable();
    }
}
