<?php

/**
 * Migration: create roles and pivot tables for SwiftAuth.
 *
 * PHP 8.1+
 *
 * @package Equidna\SwiftAuth\Database\Migrations
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');
        $rolesTable = $prefix . 'Roles';
        $usersTable = $prefix . 'Users';
        $pivotTable = $prefix . 'UsersRoles';

        if (!Schema::hasTable($rolesTable)) {
            Schema::create($rolesTable, function (Blueprint $table) {
                $table->id('id_role');
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->json('actions')->nullable();
                $table->timestamps();

                $table->index('name');
            });
        }

        if (!Schema::hasTable($pivotTable) && Schema::hasTable($usersTable) && Schema::hasTable($rolesTable)) {
            Schema::create($pivotTable, function (Blueprint $table) use ($usersTable, $rolesTable) {
                $table->unsignedBigInteger('id_user');
                $table->unsignedBigInteger('id_role');
                $table->primary(['id_user', 'id_role']);
                $table->foreign('id_user')->references('id_user')->on($usersTable)->onDelete('cascade');
                $table->foreign('id_role')->references('id_role')->on($rolesTable)->onDelete('cascade');

                $table->index('id_user');
                $table->index('id_role');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift-auth_');
        Schema::dropIfExists($prefix . 'UsersRoles');
        Schema::dropIfExists($prefix . 'Roles');
    }
};
