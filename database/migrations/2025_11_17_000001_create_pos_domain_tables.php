<?php

/**
 * Migration: create POS domain tables.
 *
 * Creates domain-specific tables used by the POS sub-system (products, sales,
 * inventories and related entities).
 *
 * PHP 8.1+
 *
 * @package   Database\Migrations
 */

/**
 * Migration: create POS domain tables.
 *
 * Adds tables required by the POS domain (products, sales, inventories, etc.).
 *
 * PHP 8.1+
 *
 * @package   Database\Migrations
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('code', 32)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('code', 32)->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->string('short_description', 160);
            $table->text('long_description')->nullable();
            $table->string('photo_url')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->dateTime('entry_date');
            $table->dateTime('stock_end_date')->nullable();
            $table->foreignUuid('product_type_id')->constrained('product_types');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('short_description');
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reorder_point')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::create('reserved_sku_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('prefix', 16)->nullable();
            $table->unsignedBigInteger('from');
            $table->unsignedBigInteger('to');
            $table->unsignedBigInteger('used_up_to')->nullable();
            $table->string('purpose', 120);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('email', 160)->nullable()->unique();
            $table->string('phone', 32)->nullable();
            $table->boolean('accepts_marketing')->default(false);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('visual_key', 12)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('folio', 32)->unique();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'mixed']);
            $table->json('payment_details')->nullable();
            $table->decimal('total_gross', 12, 2);
            $table->decimal('discount_total', 12, 2);
            $table->decimal('total_net', 12, 2);
            $table->dateTime('paid_at');
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->string('sku', 64);
            $table->string('description', 160);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('swift_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event');
            $table->nullableMorphs('auditable');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('folio_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->unique('warehouse_id');
            $table->string('prefix', 8)->default('POS');
            $table->unsignedBigInteger('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_sequences');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('swift_tokens');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('reserved_sku_ranges');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('warehouses');
    }
};
