<?php

namespace Tests\Unit;

use App\Domain\Catalog\SkuGeneratorService;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReservedSkuRange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_reserves_unique_skus_without_collisions(): void
    {
        $range = ReservedSkuRange::create([
            'prefijo' => 'PX',
            'desde' => 100,
            'hasta' => 200,
            'proposito' => 'test',
            'usado_hasta' => 102,
        ]);

        $type = ProductType::factory()->create();
        Product::factory()->create([
            'sku' => 'PX000103',
            'product_type_id' => $type->id,
        ]);

        $skus = app(SkuGeneratorService::class)->reserve(3);

        $this->assertEquals($range->id, $skus['rango_id']);
        $this->assertEquals(['PX000104', 'PX000105', 'PX000106'], $skus['skus']);
    }
}
