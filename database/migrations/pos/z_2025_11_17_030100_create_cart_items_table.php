<?php

/**
 * Migration: create cart_items table.
 *
 * PHP 8.1+
 *
 * @package   Database\Migrations\POS
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Runs the migration.
     *
     * @return void
     */
    public function up(): void
    {
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
    }

    /**
     * Reverses the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
