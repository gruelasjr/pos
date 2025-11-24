<?php

/**
 * Product model factory.
 *
 * PHP 8.1+
 *
 * @package   Database\Factories
 */

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
            'short_description' => fake()->words(3, true),
            'long_description' => fake()->sentence(10),
            'purchase_price' => fake()->randomFloat(2, 10, 200),
            'sale_price' => fake()->randomFloat(2, 20, 400),
            'entry_date' => now(),
            'product_type_id' => ProductType::factory(),
            'active' => true,
        ];
    }
}
