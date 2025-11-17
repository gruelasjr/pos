<?php

namespace Database\Factories;

use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'sku' => strtoupper(fake()->bothify('PRD####')),
            'descripcion_corta' => fake()->words(3, true),
            'descripcion_larga' => fake()->sentence(10),
            'precio_compra' => fake()->randomFloat(2, 10, 200),
            'precio_venta' => fake()->randomFloat(2, 20, 400),
            'fecha_ingreso' => now(),
            'product_type_id' => ProductType::factory(),
            'activo' => true,
        ];
    }
}
