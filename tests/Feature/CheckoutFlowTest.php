<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReservedSkuRange;
use App\Models\User;
use App\Models\Warehouse;
use Equdna\SwiftAuth\Services\SwiftAuthManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_checkout_creates_sale_and_updates_inventory(): void
    {
        Bus::fake();

        $user = User::factory()->create(['role' => 'vendedor']);
        $warehouse = Warehouse::factory()->create();
        $type = ProductType::factory()->create();
        $product = Product::factory()->create([
            'product_type_id' => $type->id,
            'precio_venta' => 150,
        ]);
        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'existencias' => 5,
            'punto_reorden' => 1,
        ]);
        ReservedSkuRange::create([
            'prefijo' => 'PX',
            'desde' => 1000,
            'hasta' => 2000,
            'proposito' => 'test',
        ]);

        $token = app(SwiftAuthManager::class)->issueToken($user->id, 'tests');

        $this->withToken($token['token'])
            ->postJson('/api/v1/carts', ['almacen_id' => $warehouse->id])
            ->assertSuccessful();

        $cartId = $this->getJson('/api/v1/carts?per_page=1')->json('data.0.id');

        $this->withToken($token['token'])
            ->postJson("/api/v1/carts/{$cartId}/items", [
                'producto_id' => $product->id,
                'cantidad' => 2,
            ])
            ->assertSuccessful();

        $this->withToken($token['token'])
            ->postJson("/api/v1/carts/{$cartId}/checkout", [
                'metodo_pago' => 'efectivo',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.total_neto', 300);

        $this->assertDatabaseHas('sales', [
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'total_neto' => 300,
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'existencias' => 3,
        ]);
    }
}
