<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query()->where('is_active', true);

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->get('status')) {
            $query->when($status === 'Aman', fn($q) => $q->whereRaw('current_stock > minimum_stock'))
                  ->when($status === 'Menipis', fn($q) => $q->whereRaw('current_stock > 0 AND current_stock <= minimum_stock'))
                  ->when($status === 'Habis', fn($q) => $q->where('current_stock', '<=', 0));
        }

        $materials    = $query->orderBy('name')->paginate(15)->withQueryString();
        $allMaterials = Material::where('is_active', true)->orderBy('name')->get();

        // Stats
        $allActive = Material::where('is_active', true)->get();
        $stats = [
            'total'  => $allActive->count(),
            'safe'   => $allActive->filter(fn($m) => $m->status === 'Aman')->count(),
            'low'    => $allActive->filter(fn($m) => $m->status === 'Menipis')->count(),
            'empty'  => $allActive->filter(fn($m) => $m->status === 'Habis')->count(),
        ];

        $movements = StockMovement::with(['material', 'user'])
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'mov_page');

        return view('inventory.index', compact('materials', 'allMaterials', 'stats', 'movements'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:150|unique:materials,name',
            'unit'          => 'required|string|max:30',
            'current_stock' => 'required|numeric|min:0',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $material = Material::create([
            ...$validated,
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]);

        // Catat pergerakan stok awal
        if ($material->current_stock > 0) {
            StockMovement::create([
                'material_id'     => $material->id,
                'user_id'         => auth()->id(),
                'sale_id'         => null,
                'movement_type'   => 'purchase_in',
                'quantity_change' => $material->current_stock,
                'stock_before'    => 0,
                'stock_after'     => $material->current_stock,
                'source'          => 'Penambahan Bahan Baru',
                'notes'           => 'Stok awal saat bahan baru didaftarkan',
                'created_at'      => now(),
            ]);
        }

        return back()->with('success', "Bahan baku \"{$material->name}\" berhasil ditambahkan.");
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150', Rule::unique('materials', 'name')->ignore($material->id)],
            'unit'          => 'required|string|max:30',
            'minimum_stock' => 'required|numeric|min:0',
        ]);

        $material->update([
            ...$validated,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', "Bahan baku \"{$material->name}\" berhasil diperbarui.");
    }

    public function destroy(Material $material)
    {
        // Soft delete: nonaktifkan daripada hapus permanen
        $material->update(['is_active' => false, 'updated_by' => auth()->id()]);
        return back()->with('success', "Bahan baku \"{$material->name}\" berhasil dinonaktifkan.");
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'material_id'   => 'required|exists:materials,id',
            'type'          => 'required|in:in,out,set',
            'quantity'      => 'required|numeric|min:0',
            'note'          => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($validated) {
            $material = Material::lockForUpdate()->findOrFail($validated['material_id']);
            $stockBefore = (float) $material->current_stock;
            $qty = (float) $validated['quantity'];
            $note = $validated['note'] ?? '';

            switch ($validated['type']) {
                case 'in':
                    $stockAfter   = $stockBefore + $qty;
                    $change       = $qty;
                    $movementType = 'manual_in';
                    $source       = 'Penyesuaian Manual (Stok Masuk)';
                    break;

                case 'out':
                    if ($qty > $stockBefore) {
                        throw new \Exception("Jumlah koreksi ({$qty}) melebihi stok saat ini ({$stockBefore} {$material->unit}).");
                    }
                    $stockAfter   = $stockBefore - $qty;
                    $change       = -$qty;
                    $movementType = 'manual_out';
                    $source       = 'Penyesuaian Manual (Stok Keluar)';
                    break;

                case 'set':
                    $stockAfter   = $qty;
                    $change       = $qty - $stockBefore;
                    $movementType = 'physical_adjustment';
                    $source       = 'Penyesuaian Stok Fisik';
                    break;

                default:
                    throw new \Exception('Jenis penyesuaian tidak valid.');
            }

            $material->update(['current_stock' => $stockAfter]);

            StockMovement::create([
                'material_id'     => $material->id,
                'user_id'         => auth()->id(),
                'sale_id'         => null,
                'movement_type'   => $movementType,
                'quantity_change' => $change,
                'stock_before'    => $stockBefore,
                'stock_after'     => $stockAfter,
                'source'          => $source,
                'notes'           => $note ?: 'Penyesuaian stok manual',
                'created_at'      => now(),
            ]);
        });

        return back()->with('success', 'Penyesuaian stok berhasil disimpan dan tercatat dalam riwayat.');
    }
}
