<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('codigo', 32)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('product_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120);
            $table->string('codigo', 32)->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 64)->unique();
            $table->string('descripcion_corta', 160);
            $table->text('descripcion_larga')->nullable();
            $table->string('foto_url')->nullable();
            $table->decimal('precio_compra', 12, 2)->default(0);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->dateTime('fecha_ingreso');
            $table->dateTime('fecha_fin_stock')->nullable();
            $table->foreignUuid('product_type_id')->constrained('product_types');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index('descripcion_corta');
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products');
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->unsignedInteger('existencias')->default(0);
            $table->unsignedInteger('punto_reorden')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::create('reserved_sku_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('prefijo', 16)->nullable();
            $table->unsignedBigInteger('desde');
            $table->unsignedBigInteger('hasta');
            $table->unsignedBigInteger('usado_hasta')->nullable();
            $table->string('proposito', 120);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 160);
            $table->string('email', 160)->nullable()->unique();
            $table->string('telefono', 32)->nullable();
            $table->boolean('acepta_marketing')->default(false);
            $table->timestamps();
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clave_visual', 12)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->enum('estado', ['activo', 'en_pausa', 'cerrado'])->default('activo');
            $table->decimal('total_bruto', 12, 2)->default(0);
            $table->decimal('descuento_total', 12, 2)->default(0);
            $table->decimal('total_neto', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('folio', 32)->unique();
            $table->foreignUuid('warehouse_id')->constrained('warehouses');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers');
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'mixto']);
            $table->json('pagos_detalle')->nullable();
            $table->decimal('total_bruto', 12, 2);
            $table->decimal('descuento_total', 12, 2);
            $table->decimal('total_neto', 12, 2);
            $table->dateTime('pagado_en');
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products');
            $table->string('sku', 64);
            $table->string('descripcion', 160);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('swift_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event');
            $table->nullableMorphs('auditable');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('folio_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('warehouse_id')->constrained('warehouses')->unique();
            $table->string('prefijo', 8)->default('POS');
            $table->unsignedBigInteger('consecutivo')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folio_sequences');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('swift_tokens');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('reserved_sku_ranges');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_types');
        Schema::dropIfExists('warehouses');
    }
};
