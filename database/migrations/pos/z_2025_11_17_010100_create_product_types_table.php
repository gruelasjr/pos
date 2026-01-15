<?php

/**
 * Migration: create product_types table.
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
        Schema::create('product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('code', 32)->unique();
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
        Schema::dropIfExists('product_types');
    }
};
