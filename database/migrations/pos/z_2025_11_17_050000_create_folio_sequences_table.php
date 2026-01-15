<?php

/**
 * Migration: create folio_sequences table.
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
        Schema::create('folio_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->unique('warehouse_id');
            $table->string('prefix', 8)->default('POS');
            $table->unsignedBigInteger('sequence')->default(1);
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
        Schema::dropIfExists('folio_sequences');
    }
};
