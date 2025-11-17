<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReservedSkuRange;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin POS',
            'email' => 'admin@pos.local',
            'role' => 'admin',
            'password' => Hash::make('secret'),
        ]);

        $seller = User::factory()->create([
            'name' => 'Vendedor Demo',
            'email' => 'vendedor@pos.local',
            'role' => 'vendedor',
            'password' => Hash::make('secret'),
        ]);

        User::factory()->create([
            'name' => 'Auditor Demo',
            'email' => 'auditor@pos.local',
            'role' => 'auditor',
            'password' => Hash::make('secret'),
        ]);

        $warehouses = Warehouse::factory()->count(2)->create();
        $types = ProductType::factory()->count(3)->create();

        $products = Product::factory()
            ->count(12)
            ->state(function () use ($types) {
                return ['product_type_id' => $types->random()->id];
            })
            ->create();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                Inventory::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'existencias' => random_int(5, 30),
                    'punto_reorden' => 5,
                ]);
            }
        }

        ReservedSkuRange::create([
            'prefijo' => 'P',
            'desde' => 1000,
            'hasta' => 9999,
            'proposito' => 'General',
        ]);
    }
}
