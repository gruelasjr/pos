<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pos_users', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 64)->index();
            $table->string('uri_user', 191);
            $table->string('name', 150);
            $table->string('email', 150);
            $table->json('roles')->nullable();
            $table->unique(['tenant_id', 'uri_user']);
            $table->unique(['tenant_id', 'email']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('tenant_id', 64)->index();
            $table->string('event', 120)->index();
            $table->nullableMorphs('auditable');
            $table->foreignId('user_id')->nullable()->constrained('pos_users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('pos_users');
    }
};
