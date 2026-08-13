<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_metadata_coded_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 64)->index();
            $table->foreignUuid('definition_id')->constrained('product_metadata_definitions')->cascadeOnDelete();
            $table->string('value', 120);
            $table->string('code', 16);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'definition_id', 'value']);
            $table->unique(['tenant_id', 'definition_id', 'code']);
        });

        Schema::table('reserved_sku_ranges', function (Blueprint $table) {
            $table->string('composed_prefix', 255)->nullable()->after('id');
        });

        Schema::create('reserved_sku_range_segments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 64)->index();
            $table->foreignUuid('range_id')->constrained('reserved_sku_ranges')->cascadeOnDelete();
            $table->foreignUuid('definition_id')->constrained('product_metadata_definitions')->restrictOnDelete();
            $table->foreignUuid('coded_value_id')->constrained('product_metadata_coded_values')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
            $table->unique(['range_id', 'definition_id']);
            $table->unique(['range_id', 'position']);
        });

        DB::table('reserved_sku_ranges')->update(['active' => false]);

        Schema::table('reserved_sku_ranges', function (Blueprint $table) {
            $table->dropColumn(['prefix', 'purpose']);
            $table->unique(['tenant_id', 'composed_prefix', 'from', 'to'], 'sku_ranges_composition_bounds_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserved_sku_range_segments');
        Schema::dropIfExists('product_metadata_coded_values');
        Schema::table('reserved_sku_ranges', function (Blueprint $table) {
            $table->dropUnique('sku_ranges_composition_bounds_unique');
            $table->dropColumn('composed_prefix');
            $table->string('prefix', 16)->nullable();
            $table->string('purpose', 120)->default('General');
        });
    }
};
