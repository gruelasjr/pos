<?php

/**
 * Migration: create reserved_sku_ranges table.
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
        Schema::create('reserved_sku_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('prefix', 16)->nullable();
            $table->unsignedBigInteger('from');
            $table->unsignedBigInteger('to');
            $table->unsignedBigInteger('used_up_to')->nullable();
            $table->string('purpose', 120);
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
        Schema::dropIfExists('reserved_sku_ranges');
    }
};
