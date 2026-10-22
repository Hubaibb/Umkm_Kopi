<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\Material;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleMaterialUsage;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    /**
     * Proses checkout dengan atomic transaction dan row locking.
     *
     * @param  array  $cartItems  [['menu_id' => 1, 'quantity' => 2], ...]
     * @param  string $paymentMethod  'cash' | 'qris'
     * @param  int    $cashierId
     * @param  string|null $notes
     * @return Sale
     * @throws \Exception
     */
    public function processCheckout(array $cartItems, string $paymentMethod, int $cashierId, ?string $notes = null): Sale
    {
        return DB::transaction(function () use ($cartItems, $paymentMethod, $cashierId, $notes) {
            // ── 1. Load menu items ─────────────────────────────────────────────
            $menuIds = array_column($cartItems, 'menu_id');
            $menus   = Menu::with(['recipeItems.material'])
                ->whereIn('id', $menuIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            // Validasi semua menu tersedia
            foreach ($cartItems as $item) {
                if (!$menus->has($item['menu_id'])) {
                    throw new \Exception("Menu dengan ID {$item['menu_id']} tidak tersedia.");
                }
            }

            // ── 2. Hitung kebutuhan total bahan ────────────────────────────────
            $materialNeeds = []; // material_id => total_quantity

            foreach ($cartItems as $item) {
                $menu = $menus->get($item['menu_id']);
                $qty  = (int) $item['quantity'];

                foreach ($menu->recipeItems as $recipeItem) {
                    $matId = $recipeItem->material_id;
                    $materialNeeds[$matId] = ($materialNeeds[$matId] ?? 0) + ($recipeItem->quantity_per_serving * $qty);
                }
            }

            // ── 3. Lock baris material (SELECT FOR UPDATE) ────────────────────
            $materialIds = array_keys($materialNeeds);
            $materials   = Material::whereIn('id', $materialIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // ── 4. Validasi kecukupan stok ────────────────────────────────────
            $shortages = [];
            foreach ($materialNeeds as $matId => $needed) {
                $material = $materials->get($matId);
                if (!$material || $material->current_stock < $needed) {
                    $shortages[] = $material ? $material->name : "Material #{$matId}";
                }
            }

            if (!empty($shortages)) {
                throw new \Exception(
                    'Stok tidak cukup untuk: ' . implode(', ', $shortages) . '. Periksa stok sebelum melanjutkan.'
                );
            }

            // ── 5. Buat Sale ──────────────────────────────────────────────────
            $transactionCode = $this->generateTransactionCode();
            $subtotal = 0;

            foreach ($cartItems as $item) {
                $menu = $menus->get($item['menu_id']);
                $subtotal += $menu->price * $item['quantity'];
            }

            /** @var Sale $sale */
            $sale = Sale::create([
                'transaction_code'   => $transactionCode,
                'cashier_id'         => $cashierId,
                'transaction_status' => 'completed',
                'payment_method'     => $paymentMethod,
                'subtotal'           => $subtotal,
                'total'              => $subtotal,
                'notes'              => $notes,
                'completed_at'       => now(),
            ]);

            // ── 6. Buat SaleItems dan SaleMaterialUsages ──────────────────────
            foreach ($cartItems as $item) {
                $menu     = $menus->get($item['menu_id']);
                $qty      = (int) $item['quantity'];
                $lineTotal = $menu->price * $qty;

                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::create([
                    'sale_id'            => $sale->id,
                    'menu_id'            => $menu->id,
                    'menu_name_snapshot' => $menu->name,
                    'unit_price'         => $menu->price,
                    'quantity'           => $qty,
                    'line_total'         => $lineTotal,
                ]);

                // Catat pemakaian material per item
                foreach ($menu->recipeItems as $recipeItem) {
                    $material  = $materials->get($recipeItem->material_id);
                    $usedQty   = $recipeItem->quantity_per_serving * $qty;

                    SaleMaterialUsage::create([
                        'sale_id'               => $sale->id,
                        'sale_item_id'          => $saleItem->id,
                        'material_id'           => $material->id,
                        'material_name_snapshot'=> $material->name,
                        'unit_snapshot'         => $material->unit,
                        'quantity_used'         => $usedQty,
                        'created_at'            => now(),
                    ]);
                }
            }

            // ── 7. Kurangi stok dan catat StockMovement ───────────────────────
            foreach ($materialNeeds as $matId => $totalUsed) {
                $material    = $materials->get($matId);
                $stockBefore = (float) $material->current_stock;
                $stockAfter  = $stockBefore - $totalUsed;

                // Update stok
                $material->update(['current_stock' => $stockAfter]);

                // Catat pergerakan stok
                StockMovement::create([
                    'material_id'    => $matId,
                    'user_id'        => $cashierId,
                    'sale_id'        => $sale->id,
                    'movement_type'  => 'sale_usage',
                    'quantity_change' => -$totalUsed,
                    'stock_before'   => $stockBefore,
                    'stock_after'    => $stockAfter,
                    'source'         => 'Transaksi ' . $transactionCode,
                    'notes'          => 'Pemakaian otomatis dari transaksi POS',
                    'created_at'     => now(),
                ]);
            }

            return $sale->load(['items', 'cashier']);
        });
    }

    /**
     * Generate kode transaksi unik: TRX-YYYYMMDD-XXXX
     */
    private function generateTransactionCode(): string
    {
        $date   = now()->format('Ymd');
        $prefix = "TRX-{$date}-";

        // Cari counter terakhir untuk hari ini
        $last = Sale::where('transaction_code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('transaction_code');

        if ($last) {
            $counter = (int) substr($last, strlen($prefix)) + 1;
        } else {
            $counter = 1;
        }

        return $prefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }
}
