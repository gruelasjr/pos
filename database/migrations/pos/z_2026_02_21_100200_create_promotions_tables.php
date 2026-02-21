<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('promotions')) {
            Schema::create('promotions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name', 120);
                $table->enum('type', ['percentage', 'fixed']);
                $table->decimal('value', 12, 2);
                $table->decimal('min_subtotal', 12, 2)->default(0);
                $table->unsignedInteger('priority')->default(100);
                $table->boolean('stackable')->default(false);
                $table->boolean('active')->default(true);
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('ends_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('promotion_rules')) {
            Schema::create('promotion_rules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
                $table->string('rule_type', 80)->default('cart');
                $table->json('rule_payload')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (! Schema::hasColumn('carts', 'promotion_discount')) {
                    $table->decimal('promotion_discount', 12, 2)->default(0)->after('discount_total');
                }
                if (! Schema::hasColumn('carts', 'applied_promotions')) {
                    $table->json('applied_promotions')->nullable()->after('promotion_discount');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn(['promotion_discount', 'applied_promotions']);
        });

        Schema::dropIfExists('promotion_rules');
        Schema::dropIfExists('promotions');
    }
};
