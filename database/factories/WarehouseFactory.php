<?php

/**
 * Warehouse model factory.
 *
 * PHP 8.1+
 *
 * @package   Database\Factories
 */

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'name' => fake()->company(),
            'code' => strtoupper(fake()->lexify('ALM???')),
            'active' => true,
        ];
    }
}
