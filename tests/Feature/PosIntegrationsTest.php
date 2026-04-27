<?php

namespace Tests\Feature;

use App\Jobs\SendReceiptJob;
use App\Models\Inventory;
use App\Models\PosIntegrationEvent;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\BuildsPosFixtures;
use Tests\TestCase;

class PosIntegrationsTest extends TestCase
{
    use BuildsPosFixtures;
    use RefreshDatabase;

    public function test_checkout_declines_mock_payment_without_creating_sale(): void
    {
        Queue::fake();

        $seller = User::factory()->seller()->create();
        [$warehouse, $product] = $this->posCatalog(stock: 3);
        $token = $this->bearerTokenFor($seller);
        $cartId = $this->createCartWithItem($token, $warehouse->id, $product->id);

        $this
            ->withToken($token)
            ->withHeader('X-Idempotency-Key', 'declined-payment')
            ->postJson("/api/v1/carts/{$cartId}/checkout", [
                'payment_method' => 'card',
                'payment_details' => ['mock_decline' => true],
            ])
            ->assertUnprocessable();

        $this->assertSame(0, Sale::count());
        $this->assertSame(3, Inventory::where('product_id', $product->id)->value('stock'));
        $this->assertDatabaseHas('pos_integration_events', [
            'sale_id' => null,
            'operation' => 'payment.charge',
            'provider' => 'mock-payment',
            'status' => 'failed',
        ]);
        Queue::assertNotPushed(SendReceiptJob::class);
    }

    public function test_admin_can_issue_mock_fiscal_document_for_sale(): void
    {
        Queue::fake();

        $seller = User::factory()->seller()->create();
        $admin = User::factory()->admin()->create();
        [$warehouse, $product] = $this->posCatalog();
        $sellerToken = $this->bearerTokenFor($seller);
        $adminToken = $this->bearerTokenFor($admin);
        $cartId = $this->createCartWithItem($sellerToken, $warehouse->id, $product->id);

        $saleId = $this
            ->withToken($sellerToken)
            ->withHeader('X-Idempotency-Key', 'fiscal-sale')
            ->postJson("/api/v1/carts/{$cartId}/checkout", ['payment_method' => 'cash'])
            ->assertOk()
            ->json('data.id');

        $this
            ->withToken($adminToken)
            ->postJson("/api/v1/sales/{$saleId}/fiscal-document", [
                'customer' => ['tax_id' => 'XAXX010101000'],
            ])
            ->assertOk()
            ->assertJsonPath('data.provider', 'mock-fiscal')
            ->assertJsonPath('data.status', 'issued');

        $sale = Sale::findOrFail($saleId);
        $this->assertSame('issued', $sale->fiscal_status);
        $this->assertNotNull($sale->fiscal_uuid);
        $this->assertDatabaseHas('pos_integration_events', [
            'sale_id' => $saleId,
            'operation' => 'fiscal.issue',
            'provider' => 'mock-fiscal',
            'status' => 'success',
        ]);
    }

    public function test_barcode_scanner_mock_parses_quantity_and_sku(): void
    {
        $seller = User::factory()->seller()->create();
        $token = $this->bearerTokenFor($seller);

        $this
            ->withToken($token)
            ->postJson('/api/v1/hardware/barcode/parse', ['input' => '3*p1000'])
            ->assertOk()
            ->assertJsonPath('data.provider', 'mock-barcode-scanner')
            ->assertJsonPath('data.sku', 'P1000')
            ->assertJsonPath('data.quantity', 3);
    }

    public function test_admin_can_open_mock_cash_drawer_manually(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->bearerTokenFor($admin);

        $this
            ->withToken($token)
            ->postJson('/api/v1/hardware/cash-drawer/open', ['reason' => 'cash count'])
            ->assertOk()
            ->assertJsonPath('data.provider', 'mock-cash-drawer')
            ->assertJsonPath('data.status', 'opened');

        $this->assertDatabaseHas('pos_integration_events', [
            'sale_id' => null,
            'operation' => 'cash_drawer.open_manual',
            'provider' => 'mock-cash-drawer',
            'status' => 'success',
        ]);
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
