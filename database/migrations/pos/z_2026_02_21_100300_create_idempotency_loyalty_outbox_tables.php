<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('idempotency_keys')) {
            Schema::create('idempotency_keys', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('key', 120)->unique();
                $table->string('route', 180);
                $table->string('method', 12);
                $table->string('request_hash', 64);
                $table->json('response_body');
                $table->unsignedSmallInteger('status_code');
                $table->foreignId('user_id')->nullable()->constrained('swift_auth_Users', 'id_user');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('loyalty_accounts')) {
            Schema::create('loyalty_accounts', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('customer_id')->unique()->constrained('customers');
                $table->unsignedInteger('points_balance')->default(0);
                $table->unsignedInteger('lifetime_points')->default(0);
                $table->string('tier', 32)->default('base');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('loyalty_movements')) {
            Schema::create('loyalty_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('loyalty_account_id')->constrained('loyalty_accounts')->cascadeOnDelete();
                $table->foreignUuid('sale_id')->nullable()->constrained('sales');
                $table->enum('type', ['earn', 'redeem', 'adjust']);
                $table->integer('points');
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 64)->unique();
                $table->foreignUuid('customer_id')->nullable()->constrained('customers');
                $table->enum('type', ['fixed', 'percent']);
                $table->decimal('value', 12, 2);
                $table->boolean('active')->default(true);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('outbox_messages')) {
            Schema::create('outbox_messages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('event_type', 120);
                $table->string('aggregate_type', 120)->nullable();
                $table->string('aggregate_id', 64)->nullable();
                $table->json('payload');
                $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
                $table->unsignedInteger('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->dateTime('available_at')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'available_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('loyalty_movements');
        Schema::dropIfExists('loyalty_accounts');
        Schema::dropIfExists('idempotency_keys');
    }
};
