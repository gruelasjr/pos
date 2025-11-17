<?php

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
            'nombre' => fake()->company(),
            'codigo' => strtoupper(fake()->lexify('ALM???')),
            'activo' => true,
        ];
    }
}
