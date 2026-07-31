<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('idempotency_key', 160);
            $table->enum('method', ['cash', 'card', 'transfer', 'mixed']);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('MXN');
            $table->enum('status', ['pending', 'paid', 'failed', 'reconciliation_required'])->default('pending');
            $table->string('provider', 64)->nullable();
            $table->string('provider_reference', 160)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->text('failure_message')->nullable();
            $table->dateTime('attempted_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['sale_id', 'idempotency_key']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('customer_registration_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignUuid('sale_id')->unique()->constrained('sales')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->dateTime('used_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'expires_at']);
        });

        Schema::table('outbox_messages', function (Blueprint $table) {
            $table->string('lock_token', 64)->nullable()->after('last_error');
            $table->dateTime('locked_at')->nullable()->after('lock_token');
            $table->dateTime('dead_lettered_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('outbox_messages', fn (Blueprint $table) => $table->dropColumn(['lock_token', 'locked_at', 'dead_lettered_at']));
        Schema::dropIfExists('customer_registration_links');
        Schema::dropIfExists('payment_attempts');
    }
};
