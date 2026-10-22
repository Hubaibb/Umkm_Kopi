<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration disesuaikan dengan schema existing di database umkm_kopi.
 * Tabel-tabel ini sudah ada di database, migration ini digunakan
 * untuk referensi dan environment baru saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel roles
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name', 60)->unique();
                $table->string('slug', 60)->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Tabel permissions
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 100)->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Tabel role_permissions
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained()->onDelete('cascade');
                $table->foreignId('permission_id')->constrained()->onDelete('cascade');
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->primary(['role_id', 'permission_id']);
            });
        }

        // Tabel users
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained()->onDelete('restrict');
                $table->string('name', 150);
                $table->string('username', 80)->unique();
                $table->string('email', 190)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->boolean('is_active')->default(true)->index();
                $table->rememberToken();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }

        // Tabel sessions
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
