<?php

/**
 * Migration: create customers table.
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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 160);
            $table->string('email', 160)->nullable()->unique();
            $table->string('phone', 32)->nullable();
            $table->boolean('accepts_marketing')->default(false);
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
        Schema::dropIfExists('customers');
    }
};
