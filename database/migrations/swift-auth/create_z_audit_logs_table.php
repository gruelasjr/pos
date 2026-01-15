<?php

/**
 * Migration: create audit_logs table for SwiftAuth.
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

        Schema::create($prefix . 'AuditLogs', function (Blueprint $table) use ($prefix) {
            $table->uuid('id')->primary();
            $table->string('event');
            $table->nullableMorphs('auditable');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Foreign key to Users
            $table->foreign('id_user')
                ->references('id_user')
                ->on($prefix . 'Users')
                ->nullOnDelete();

            // Performance indexes
            $table->index('event');
            $table->index('id_user');
            $table->index('created_at');
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
        Schema::dropIfExists($prefix . 'AuditLogs');
    }
};
