<?php

/**
 * Migration: create carts table.
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
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('visual_key', 12)->unique();
            $table->foreignId('user_id')->constrained('swift_auth_Users');
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->enum('status', ['active', 'paused', 'closed'])->default('active');
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
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
        Schema::dropIfExists('carts');
    }
};
