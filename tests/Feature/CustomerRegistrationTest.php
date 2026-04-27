<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsPosFixtures;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use BuildsPosFixtures;
    use RefreshDatabase;

    public function test_customer_registration_requires_secure_single_use_token(): void
    {
        $seller = User::factory()->seller()->create();
        [$warehouse] = $this->posCatalog();

        $sale = Sale::create([
            'folio' => 'POS-0001',
            'warehouse_id' => $warehouse->id,
            'user_id' => $seller->id,
            'payment_method' => 'cash',
            'total_gross' => 100,
            'discount_total' => 0,
            'total_net' => 100,
            'paid_at' => now(),
        ]);
        $token = $sale->issueCustomerRegistrationToken();

        $this->postJson('/api/v1/customers/register', [
            'token' => $sale->folio,
            'name' => 'Cliente Incorrecto',
        ])->assertNotFound();

        $this->postJson('/api/v1/customers/register', [
            'token' => $token,
            'name' => 'Cliente Faro',
            'email' => 'cliente@example.test',
            'phone' => '555-0101',
            'accepts_marketing' => true,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $sale->refresh();
        $this->assertNotNull($sale->customer_id);
        $this->assertNotNull($sale->customer_registration_used_at);
        $this->assertSame('Cliente Faro', Customer::find($sale->customer_id)->name);

        $this->postJson('/api/v1/customers/register', [
            'token' => $token,
            'name' => 'Reuso',
        ])->assertNotFound();
    }
}
