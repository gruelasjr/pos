<?php

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
            'nombre' => fake()->word(),
            'codigo' => strtoupper(fake()->lexify('TP???')),
        ];
    }
}
