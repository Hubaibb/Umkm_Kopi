<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk tabel operasional - disesuaikan dengan schema existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel categories
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 110)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        // Tabel materials (bahan baku)
        if (!Schema::hasTable('materials')) {
            Schema::create('materials', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->string('unit', 30);
                $table->decimal('current_stock', 14, 3)->default(0);
                $table->decimal('minimum_stock', 14, 3)->default(0);
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Tabel menus
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->onDelete('restrict');
                $table->string('name', 150)->index();
                $table->string('slug', 160)->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Tabel recipe_items (BOM / Resep)
        if (!Schema::hasTable('recipe_items')) {
            Schema::create('recipe_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained()->onDelete('cascade');
                $table->foreignId('material_id')->constrained()->onDelete('restrict');
                $table->decimal('quantity_per_serving', 14, 3);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->unique(['menu_id', 'material_id']);
                $table->index('menu_id');
                $table->index('material_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recipe_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('categories');
    }
};
