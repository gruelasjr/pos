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
        $this->replaceUnique('loyalty_accounts', ['customer_id'], ['tenant_id', 'customer_id'], true);
        $this->replaceUnique('carts', ['visual_key'], ['tenant_id', 'visual_key']);
        $this->replaceUnique('folio_sequences', ['warehouse_id'], ['tenant_id', 'warehouse_id'], true);
        $this->replaceUnique(
            'inventories',
            ['product_id', 'warehouse_id'],
            ['tenant_id', 'product_id', 'warehouse_id'],
            true
        );
        $this->addUniqueIfMissing(
            'payment_attempts',
            ['tenant_id', 'idempotency_key'],
            'payment_attempts_tenant_key_unique'
        );
    }

    private function replaceUnique(
        string $tableName,
        array $old,
        array $replacement,
        bool $preserveOldColumnsAsIndex = false
    ): void {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $oldUniqueName = $this->indexName($tableName, $old, 'unique');
        $replacementName = $this->indexName($tableName, $replacement, 'unique');

        $this->addUniqueIfMissing($tableName, $replacement, $replacementName);

        if ($preserveOldColumnsAsIndex) {
            $supportingIndexName = $this->indexName($tableName, $old, 'index');

            if (! $this->hasIndex($tableName, $supportingIndexName)) {
                Schema::table(
                    $tableName,
                    fn (Blueprint $table) => $table->index($old, $supportingIndexName)
                );
            }
        }

        if ($this->hasIndex($tableName, $oldUniqueName)) {
            Schema::table(
                $tableName,
                fn (Blueprint $table) => $table->dropUnique($oldUniqueName)
            );
        }
    }

    private function addUniqueIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || $this->hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, fn (Blueprint $table) => $table->unique($columns, $indexName));
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $indexName);
    }

    private function indexName(string $tableName, array $columns, string $type): string
    {
        return strtolower($tableName . '_' . implode('_', $columns) . '_' . $type);
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
