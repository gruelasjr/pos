<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'customer_registration_token_hash')) {
                $table->string('customer_registration_token_hash', 64)->nullable()->unique();
            }

            if (! Schema::hasColumn('sales', 'customer_registration_expires_at')) {
                $table->dateTime('customer_registration_expires_at')->nullable();
            }

            if (! Schema::hasColumn('sales', 'customer_registration_used_at')) {
                $table->dateTime('customer_registration_used_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            foreach (
                [
                    'customer_registration_token_hash',
                    'customer_registration_expires_at',
                    'customer_registration_used_at',
                ] as $column
            ) {
                if (Schema::hasColumn('sales', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
