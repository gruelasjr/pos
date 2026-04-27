<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift_auth_');
        $usersTable = $prefix . 'Users';
        $rolesTable = $prefix . 'Roles';
        $pivotTable = $prefix . 'UsersRoles';

        if (Schema::hasTable($usersTable)) {
            Schema::table($usersTable, function (Blueprint $table) use ($usersTable) {
                if (! Schema::hasColumn($usersTable, 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique();
                }

                if (! Schema::hasColumn($usersTable, 'phone')) {
                    $table->string('phone', 32)->nullable();
                }

                if (! Schema::hasColumn($usersTable, 'active')) {
                    $table->boolean('active')->default(true);
                }
            });
        }

        if (Schema::hasTable($rolesTable)) {
            Schema::table($rolesTable, function (Blueprint $table) use ($rolesTable) {
                if (! Schema::hasColumn($rolesTable, 'slug')) {
                    $table->string('slug', 80)->nullable()->unique();
                }
            });

            DB::table($rolesTable)
                ->whereNull('slug')
                ->orderBy('id_role')
                ->get(['id_role', 'name'])
                ->each(function ($role) use ($rolesTable) {
                    DB::table($rolesTable)
                        ->where('id_role', $role->id_role)
                        ->update(['slug' => Str::slug((string) $role->name)]);
                });
        }

        if (! Schema::hasTable($pivotTable) && Schema::hasTable($usersTable) && Schema::hasTable($rolesTable)) {
            Schema::create($pivotTable, function (Blueprint $table) use ($usersTable, $rolesTable) {
                $table->unsignedBigInteger('id_user');
                $table->unsignedBigInteger('id_role');
                $table->primary(['id_user', 'id_role']);
                $table->foreign('id_user')->references('id_user')->on($usersTable)->cascadeOnDelete();
                $table->foreign('id_role')->references('id_role')->on($rolesTable)->cascadeOnDelete();
                $table->index('id_user');
                $table->index('id_role');
            });
        }
    }

    public function down(): void
    {
        $prefix = (string) config('swift-auth.table_prefix', 'swift_auth_');
        $usersTable = $prefix . 'Users';
        $rolesTable = $prefix . 'Roles';
        $pivotTable = $prefix . 'UsersRoles';

        Schema::dropIfExists($pivotTable);

        if (Schema::hasTable($rolesTable) && Schema::hasColumn($rolesTable, 'slug')) {
            Schema::table($rolesTable, function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }

        if (Schema::hasTable($usersTable)) {
            Schema::table($usersTable, function (Blueprint $table) use ($usersTable) {
                foreach (['uuid', 'phone', 'active'] as $column) {
                    if (Schema::hasColumn($usersTable, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
