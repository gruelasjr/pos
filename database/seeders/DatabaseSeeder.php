<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ReservedSkuRange;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Equidna\BeeHive\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Seeds a tenant-scoped local demo; identities remain owned by Caronte. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (string) config('app.demo_tenant_id', 'tenant-demo');
        $context = new TenantContext();
        $context->set($tenantId);
        app()->instance(TenantContext::class, $context);

        foreach ([
            ['demo-admin', 'Admin POS', 'admin@pos.local', Role::ADMIN],
            ['demo-seller', 'Vendedor Demo', 'vendedor@pos.local', Role::SELLER],
            ['demo-auditor', 'Auditor Demo', 'auditor@pos.local', Role::AUDITOR],
        ] as [$uriUser, $name, $email, $role]) {
            User::factory()->create([
                'tenant_id' => $tenantId,
                'uri_user' => $uriUser,
                'name' => $name,
                'email' => $email,
                'roles' => [['name' => $role, 'slug' => $role]],
            ]);
        }

        $warehouses = Warehouse::factory()->count(2)->create();
        $types = ProductType::factory()->count(3)->create();
        $products = Product::factory()->count(12)->state(
            fn (): array => ['product_type_id' => $types->random()->id]
        )->create();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                Inventory::create([
                    'id' => (string) Str::uuid(),
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => random_int(5, 30),
                    'reorder_point' => 5,
                ]);
            }
        }

        ReservedSkuRange::create([
            'id' => (string) Str::uuid(),
            'prefix' => 'P',
            'from' => 1000,
            'to' => 9999,
            'purpose' => 'General',
        ]);
    }
}
