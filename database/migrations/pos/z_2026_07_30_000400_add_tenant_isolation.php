<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tables = [
        'warehouses', 'product_types', 'customers', 'reserved_sku_ranges', 'products',
        'inventories', 'carts', 'cart_items', 'sales', 'sale_items', 'folio_sequences',
        'return_notes', 'return_items', 'cash_sessions', 'cash_movements', 'promotions',
        'promotion_rules', 'idempotency_keys', 'loyalty_accounts', 'loyalty_movements',
        'coupons', 'outbox_messages', 'pos_integration_events', 'payment_attempts',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('tenant_id', 64)->index();
                });
            }
        }

        $this->replaceUnique('warehouses', ['code'], ['tenant_id', 'code']);
        $this->replaceUnique('product_types', ['code'], ['tenant_id', 'code']);
        $this->replaceUnique('products', ['sku'], ['tenant_id', 'sku']);
        $this->replaceUnique('customers', ['email'], ['tenant_id', 'email']);
        $this->replaceUnique('sales', ['folio'], ['tenant_id', 'folio']);
        $this->replaceUnique('coupons', ['code'], ['tenant_id', 'code']);
        $this->replaceUnique('idempotency_keys', ['key'], ['tenant_id', 'user_id', 'route', 'key']);
        $this->replaceUnique('loyalty_accounts', ['customer_id'], ['tenant_id', 'customer_id']);
        $this->replaceUnique('carts', ['visual_key'], ['tenant_id', 'visual_key']);
        $this->replaceUnique('folio_sequences', ['warehouse_id'], ['tenant_id', 'warehouse_id']);
        $this->replaceUnique('inventories', ['product_id', 'warehouse_id'], ['tenant_id', 'product_id', 'warehouse_id']);
        Schema::table('payment_attempts', fn (Blueprint $table) => $table->unique(['tenant_id', 'idempotency_key'], 'payment_attempts_tenant_key_unique'));
    }

    private function replaceUnique(string $tableName, array $old, array $replacement): void
    {
        if (!Schema::hasTable($tableName)) return;
        Schema::table($tableName, function (Blueprint $table) use ($old, $replacement) {
            try { $table->dropUnique($old); } catch (Throwable) { /* fresh-schema compatibility */ }
            $table->unique($replacement);
        });
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('tenant_id'));
            }
        }
    }
};
