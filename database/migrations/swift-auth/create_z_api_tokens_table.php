<?php

/**
 * Migration: create API tokens table for SwiftAuth.
 *
 * Stores API tokens for users (similar to Sanctum personal access tokens
 * but integrated with SwiftAuth's user system).
 *
 * PHP 8.1+
 *
 * @package   Equidna\SwiftAuth\Database\Migrations
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
        $prefix = (string) config('swift-auth.table_prefix', 'swift_auth_');

        Schema::create($prefix . 'ApiTokens', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->unsignedBigInteger('id_user');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Foreign key to Users
            $table->foreign('id_user')
                ->references('id_user')
                ->on($prefix . 'Users')
                ->cascadeOnDelete();

            // Performance indexes
            $table->index('id_user');
            $table->index('token');
            $table->index(['id_user', 'name']);
        });
    }

    /**
     * Reverses the migration.
     *
     * @return void
     */
    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift_auth_');
        Schema::dropIfExists($prefix . 'ApiTokens');
    }
};
