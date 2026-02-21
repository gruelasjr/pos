<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('migrations')) {
            return;
        }

        $existing = DB::table('migrations')->pluck('migration')->all();

        $legacyMap = [
            'create_users_table' => (string) config('swift-auth.table_prefix', 'swift-auth_') . 'Users',
            'create_user_tokens_table' => (string) config('swift-auth.table_prefix', 'swift-auth_') . 'UserTokens',
        ];

        foreach ($legacyMap as $migrationName => $tableName) {
            if (in_array($migrationName, $existing, true)) {
                continue;
            }

            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $maxBatch = (int) DB::table('migrations')->max('batch');

            DB::table('migrations')->insert([
                'migration' => $migrationName,
                'batch' => max(1, $maxBatch),
            ]);

            $existing[] = $migrationName;
        }
    }

    public function down(): void
    {
        // no-op
    }
};
