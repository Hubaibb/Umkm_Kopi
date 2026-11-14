<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::completed()->with(['items', 'cashier']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($qi) => $qi->where('menu_name_snapshot', 'like', "%{$search}%"));
            });
        }

        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50]) ? (int) $request->input('per_page', 10) : 10;

        $sales = $query->orderByDesc('completed_at')
            ->paginate($perPage);

        $sales->appends(array_filter([
            'search'   => $request->input('search'),
            'per_page' => $perPage,
        ]));

        return view('transactions.index', compact('sales', 'perPage'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.materialUsages', 'cashier']);
        return view('transactions.show', compact('sale'));
    }
}
