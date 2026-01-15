<?php

/**
 * Migration: create products table.
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
    }

    /**
     * Reverses the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
