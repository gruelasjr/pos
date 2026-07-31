<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PosIntegrationEvent;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\BuildsPosFixtures;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use BuildsPosFixtures;
    use RefreshDatabase;

    public function test_checkout_requires_idempotency_header(): void
    {
        Queue::fake();

        $seller = User::factory()->seller()->create();
        [$warehouse, $product] = $this->posCatalog();
        $token = $this->bearerTokenFor($seller);

        $cartId = $this->createCartWithItem($token, $warehouse->id, $product->id);

        $this
            ->withToken($token)
            ->postJson("/api/v1/carts/{$cartId}/checkout", [
                'payment_method' => 'cash',
            ])
            ->assertUnprocessable();
    }

    public function test_checkout_creates_sale_decrements_inventory_and_replays_idempotently(): void
    {
        Queue::fake();

        $seller = User::factory()->seller()->create();
        [$warehouse, $product] = $this->posCatalog(stock: 5);
        $token = $this->bearerTokenFor($seller);
        $cartId = $this->createCartWithItem($token, $warehouse->id, $product->id);
        $idempotencyKey = 'checkout-test-key';

        $payload = ['payment_method' => 'cash'];

        $first = $this
            ->withToken($token)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson("/api/v1/carts/{$cartId}/checkout", $payload);

        $first
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'folio', 'items', 'customer_registration_token'],
            ]);

        $this->assertSame(1, Sale::count());
        $sale = Sale::firstOrFail();
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('mock-payment', $sale->payment_provider);
        $this->assertSame('pending', $sale->receipt_print_status);
        $this->assertSame('pending', $sale->cash_drawer_status);
        $this->assertSame(1, PosIntegrationEvent::count());
        $this->assertDatabaseHas('outbox_messages', [
            'tenant_id' => 'tenant-test',
            'status' => 'pending',
        ]);
        $this->assertSame(
            4,
            Inventory::query()
                ->where('product_id', $product->id)
                ->where('warehouse_id', $warehouse->id)
                ->value('stock')
        );
        $second = $this
            ->withToken($token)
            ->withHeader('X-Idempotency-Key', $idempotencyKey)
            ->postJson("/api/v1/carts/{$cartId}/checkout", $payload);

        $second->assertOk();
        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Sale::count());
    }

    private function createCartWithItem(string $token, string $warehouseId, string $productId): string
    {
        $cartId = $this
            ->withToken($token)
            ->postJson('/api/v1/carts', ['warehouse_id' => $warehouseId])
            ->assertOk()
            ->json('data.id');

        $this
            ->withToken($token)
            ->postJson("/api/v1/carts/{$cartId}/items", [
                'product_id' => $productId,
                'quantity' => 1,
            ])
            ->assertOk();

        return $cartId;
    }
}
