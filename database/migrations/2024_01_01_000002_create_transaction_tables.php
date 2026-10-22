<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk tabel transaksi - disesuaikan dengan schema existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tabel sales (transaksi penjualan)
        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_code', 40)->unique();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null');
                $table->enum('transaction_status', ['completed', 'cancelled'])->default('completed');
                $table->enum('payment_method', ['cash', 'qris']);
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->dateTime('completed_at');
                $table->timestamps();

                $table->index('cashier_id');
                $table->index('transaction_status');
                $table->index('created_at');
            });
        }

        // Tabel sale_items (item dalam transaksi)
        if (!Schema::hasTable('sale_items')) {
            Schema::create('sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->onDelete('cascade');
                $table->foreignId('menu_id')->nullable()->constrained()->onDelete('set null');
                $table->string('menu_name_snapshot', 150);
                $table->decimal('unit_price', 12, 2);
                $table->integer('quantity')->unsigned();
                $table->decimal('line_total', 14, 2);
                $table->timestamps();

                $table->index('sale_id');
                $table->index('menu_id');
            });
        }

        // Tabel sale_material_usages (bahan yang dipakai per transaksi)
        if (!Schema::hasTable('sale_material_usages')) {
            Schema::create('sale_material_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->onDelete('cascade');
                $table->foreignId('sale_item_id')->constrained()->onDelete('cascade');
                $table->foreignId('material_id')->nullable()->constrained()->onDelete('set null');
                $table->string('material_name_snapshot', 150);
                $table->string('unit_snapshot', 30);
                $table->decimal('quantity_used', 14, 3);
                $table->timestamp('created_at')->nullable()->useCurrent();
            });
        }

        // Tabel stock_movements (riwayat pergerakan stok)
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('material_id')->constrained()->onDelete('restrict');
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('movement_type', [
                    'purchase_in',        // stok masuk dari pembelian
                    'manual_in',          // stok masuk manual
                    'manual_out',         // stok keluar manual
                    'physical_adjustment',// sesuaikan ke stok fisik
                    'sale_usage',         // pemakaian dari transaksi
                    'correction',         // koreksi admin
                ]);
                $table->decimal('quantity_change', 14, 3); // positif = masuk, negatif = keluar
                $table->decimal('stock_before', 14, 3);
                $table->decimal('stock_after', 14, 3);
                $table->string('source', 100); // label sumber pergerakan
                $table->string('notes', 500)->nullable();
                $table->timestamp('created_at')->nullable()->useCurrent();

                $table->index('material_id');
                $table->index('user_id');
                $table->index('movement_type');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_material_usages');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
