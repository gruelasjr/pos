<?php

/**
 * ProductType model factory.
 *
 * PHP 8.1+
 *
 * @package   Database\Factories
 */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\ProductType>
 */
class ProductTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => fake()->word(),
            'code' => strtoupper(fake()->lexify('TP???')),
        ];
    }
}
