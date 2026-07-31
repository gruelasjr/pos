<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        foreach (['caronte_Users', 'caronte_UsersMetadata'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id_tenant')) {
                continue;
            }
            foreach (Schema::getConnection()->getSchemaBuilder()->getIndexes($table) as $index) {
                if (! ($index['primary'] ?? false) && in_array('id_tenant', $index['columns'] ?? [], true)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index['name']));
                }
            }
        }
    }

    public function down(): void {}
};
