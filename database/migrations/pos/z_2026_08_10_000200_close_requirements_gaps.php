<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $tenantTables = [
        'warehouses', 'product_types', 'customers', 'reserved_sku_ranges', 'products',
        'inventories', 'carts', 'cart_items', 'sales', 'sale_items', 'folio_sequences',
        'return_notes', 'return_items', 'cash_sessions', 'cash_movements', 'promotions',
        'promotion_rules', 'idempotency_keys', 'loyalty_accounts', 'loyalty_movements',
        'coupons', 'outbox_messages', 'pos_integration_events', 'payment_attempts',
        'product_tags', 'product_product_tag', 'product_metadata_definitions',
        'product_metadata_values',
    ];

    public function up(): void
    {
        $legacyTenantId = trim((string) config('app.legacy_tenant_id'));
        $tablesWithLegacyRows = collect($this->tenantTables)
            ->filter(fn (string $table): bool => Schema::hasTable($table)
                && Schema::hasColumn($table, 'tenant_id')
                && DB::table($table)->where(fn ($query) => $query
                    ->whereNull('tenant_id')->orWhere('tenant_id', ''))->exists())
            ->values();

        if ($tablesWithLegacyRows->isNotEmpty() && $legacyTenantId === '') {
            throw new \RuntimeException(
                'POS_LEGACY_TENANT_ID is required to assign historical POS records: '
                . $tablesWithLegacyRows->implode(', ')
            );
        }

        if ($legacyTenantId !== '') {
            foreach ($tablesWithLegacyRows as $table) {
                DB::table($table)->where(fn ($query) => $query
                    ->whereNull('tenant_id')->orWhere('tenant_id', ''))
                    ->update(['tenant_id' => $legacyTenantId]);
            }
        }

        foreach (['customers', 'product_types', 'reserved_sku_ranges'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'active')) {
                Schema::table($tableName, fn (Blueprint $table) => $table
                    ->boolean('active')->default(true)->index());
            }
        }
    }

    public function down(): void
    {
        foreach (['reserved_sku_ranges', 'product_types', 'customers'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'active')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('active'));
            }
        }
    }
};
