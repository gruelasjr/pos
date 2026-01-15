<?php

/**
 * Migration: create sales table.
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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('folio', 32)->unique();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('swift_auth_Users');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'mixed']);
            $table->json('payment_details')->nullable();
            $table->decimal('total_gross', 12, 2);
            $table->decimal('discount_total', 12, 2);
            $table->decimal('total_net', 12, 2);
            $table->dateTime('paid_at');
            $table->timestamps();
        });
    }

    /**
     * Reverses the migration.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
