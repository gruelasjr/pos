<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cash_sessions')) {
            Schema::create('cash_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained('swift_auth_Users', 'id_user');
                $table->foreignUuid('warehouse_id')->constrained('warehouses');
                $table->enum('status', ['open', 'closed'])->default('open');
                $table->decimal('opening_amount', 12, 2)->default(0);
                $table->decimal('closing_amount', 12, 2)->nullable();
                $table->decimal('expected_amount', 12, 2)->default(0);
                $table->decimal('difference_amount', 12, 2)->default(0);
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('cash_movements')) {
            Schema::create('cash_movements', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
                $table->enum('type', ['sale', 'refund', 'manual']);
                $table->decimal('amount', 12, 2);
                $table->string('reference_type', 120)->nullable();
                $table->string('reference_id', 64)->nullable();
                $table->string('reason', 240)->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'cash_session_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignUuid('cash_session_id')->nullable()->after('customer_id')->constrained('cash_sessions');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_session_id');
        });

        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_sessions');
    }
};
