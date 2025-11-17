<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReservedSkuRange;
use App\Models\Role;
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
        $roles = [
            'admin' => Role::firstOrCreate(
                ['slug' => 'admin'],
                [
                    'name' => 'Administrador',
                    'description' => 'Control total del POS',
                    'actions' => ['sw-admin', 'pos.manage', 'catalog.manage', 'reports.view'],
                ],
            ),
            'vendedor' => Role::firstOrCreate(
                ['slug' => 'vendedor'],
                [
                    'name' => 'Vendedor',
                    'description' => 'Opera cajas y clientes',
                    'actions' => ['pos.carts', 'pos.checkout'],
                ],
            ),
            'auditor' => Role::firstOrCreate(
                ['slug' => 'auditor'],
                [
                    'name' => 'Auditor',
                    'description' => 'Consulta reportes y catálogo',
                    'actions' => ['reports.view', 'catalog.read'],
                ],
            ),
        ];

        $admin = User::factory()->create([
            'name' => 'Admin POS',
            'email' => 'admin@pos.local',
            'password' => Hash::make('secret'),
        ]);
        $admin->roles()->sync([$roles['admin']->id]);

        $seller = User::factory()->create([
            'name' => 'Vendedor Demo',
            'email' => 'vendedor@pos.local',
            'password' => Hash::make('secret'),
        ]);
        $seller->roles()->sync([$roles['vendedor']->id]);

        $auditor = User::factory()->create([
            'name' => 'Auditor Demo',
            'email' => 'auditor@pos.local',
            'password' => Hash::make('secret'),
        ]);
        $auditor->roles()->sync([$roles['auditor']->id]);

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
