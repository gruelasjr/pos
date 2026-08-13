<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('carts', 'customer_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->foreignUuid('customer_id')->nullable()->after('warehouse_id')
                    ->constrained('customers')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('product_tags')) {
            Schema::create('product_tags', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id', 64)->index();
                $table->string('name', 80);
                $table->string('slug', 96);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id', 'slug']);
            });
        }

        if (! Schema::hasTable('product_product_tag')) {
            Schema::create('product_product_tag', function (Blueprint $table) {
                $table->string('tenant_id', 64)->index();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignUuid('product_tag_id')->constrained('product_tags')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['product_id', 'product_tag_id']);
            });
        }

        if (! Schema::hasTable('product_metadata_definitions')) {
            Schema::create('product_metadata_definitions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id', 64)->index();
                $table->string('key', 64);
                $table->string('label', 100);
                $table->enum('type', ['text', 'number', 'boolean', 'select']);
                $table->json('options')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['tenant_id', 'key']);
            });
        }

        if (! Schema::hasTable('product_metadata_values')) {
            Schema::create('product_metadata_values', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('tenant_id', 64)->index();
                $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignUuid('definition_id')->constrained('product_metadata_definitions')->cascadeOnDelete();
                $table->text('value_text')->nullable();
                $table->decimal('value_number', 18, 4)->nullable();
                $table->boolean('value_boolean')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'definition_id']);
            });
        }

        // definition_id already has an index through its foreign key. TEXT
        // remains unindexed because MySQL requires a prefix length for it.
        $this->ensureMetadataValueIndex(
            'product_metadata_values_definition_id_value_number_index',
            ['definition_id', 'value_number']
        );
        $this->ensureMetadataValueIndex(
            'product_metadata_values_definition_id_value_boolean_index',
            ['definition_id', 'value_boolean']
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_metadata_values');
        Schema::dropIfExists('product_metadata_definitions');
        Schema::dropIfExists('product_product_tag');
        Schema::dropIfExists('product_tags');
        if (Schema::hasColumn('carts', 'customer_id')) {
            Schema::table('carts', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        }
    }

    private function ensureMetadataValueIndex(string $name, array $columns): void
    {
        if (! Schema::hasIndex('product_metadata_values', $name)) {
            Schema::table('product_metadata_values', fn (Blueprint $table) => $table->index($columns, $name));
        }
    }
};
