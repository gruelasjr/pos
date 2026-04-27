<?php

namespace Tests\Feature\Concerns;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\User;
use App\Models\Warehouse;
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;
use Illuminate\Support\Str;

trait BuildsPosFixtures
{
    protected function bearerTokenFor(User $user): string
    {
        $issued = app(UserTokenService::class)->createToken($user, 'test', ['*'], now()->addHour());

        return $issued['token'];
    }

    protected function posCatalog(int $stock = 10): array
    {
        $warehouse = Warehouse::factory()->create();
        $type = ProductType::factory()->create();
        $product = Product::factory()->create([
            'product_type_id' => $type->id,
            'sale_price' => 125.50,
        ]);

        Inventory::create([
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'stock' => $stock,
            'reorder_point' => 2,
        ]);

        return [$warehouse, $product];
    }
}
