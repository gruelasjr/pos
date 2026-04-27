<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'payment_status')) {
                $table->string('payment_status', 32)->default('pending')->after('payment_details');
                $table->string('payment_provider', 64)->nullable()->after('payment_status');
                $table->string('payment_reference', 128)->nullable()->after('payment_provider');
                $table->string('payment_authorization_code', 64)->nullable()->after('payment_reference');
                $table->dateTime('payment_authorized_at')->nullable()->after('payment_authorization_code');
            }

            if (!Schema::hasColumn('sales', 'fiscal_status')) {
                $table->string('fiscal_status', 32)->default('not_requested')->after('paid_at');
                $table->string('fiscal_provider', 64)->nullable()->after('fiscal_status');
                $table->string('fiscal_reference', 128)->nullable()->after('fiscal_provider');
                $table->string('fiscal_uuid', 128)->nullable()->after('fiscal_reference');
                $table->dateTime('fiscal_issued_at')->nullable()->after('fiscal_uuid');
            }

            if (!Schema::hasColumn('sales', 'receipt_print_status')) {
                $table->string('receipt_print_status', 32)->default('pending')->after('fiscal_issued_at');
                $table->dateTime('receipt_printed_at')->nullable()->after('receipt_print_status');
                $table->string('cash_drawer_status', 32)->default('pending')->after('receipt_printed_at');
                $table->dateTime('cash_drawer_opened_at')->nullable()->after('cash_drawer_status');
            }
        });

        if (!Schema::hasTable('pos_integration_events')) {
            Schema::create('pos_integration_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->string('operation', 64);
                $table->string('provider', 64);
                $table->string('status', 32);
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->string('error_code', 64)->nullable();
                $table->text('error_message')->nullable();
                $table->dateTime('occurred_at');
                $table->timestamps();

                $table->index(['operation', 'status']);
                $table->index(['provider', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_integration_events');

        Schema::table('sales', function (Blueprint $table) {
            $columns = [
                'payment_status',
                'payment_provider',
                'payment_reference',
                'payment_authorization_code',
                'payment_authorized_at',
                'fiscal_status',
                'fiscal_provider',
                'fiscal_reference',
                'fiscal_uuid',
                'fiscal_issued_at',
                'receipt_print_status',
                'receipt_printed_at',
                'cash_drawer_status',
                'cash_drawer_opened_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
