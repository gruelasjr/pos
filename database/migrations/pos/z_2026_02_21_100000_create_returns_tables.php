<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('return_notes')) {
            Schema::create('return_notes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('sale_id')->constrained('sales');
                $table->foreignId('user_id')->constrained('pos_users');
                $table->string('reason', 240)->nullable();
                $table->decimal('total_refund', 12, 2)->default(0);
                $table->enum('status', ['approved', 'cancelled'])->default('approved');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('return_items')) {
            Schema::create('return_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('return_note_id')->constrained('return_notes')->cascadeOnDelete();
                $table->foreignUuid('sale_item_id')->constrained('sale_items');
                $table->foreignUuid('product_id')->constrained('products');
                $table->unsignedInteger('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('subtotal', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('return_notes');
    }
};
