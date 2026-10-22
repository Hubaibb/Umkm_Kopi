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

        $sales = $query->orderByDesc('completed_at')->paginate(15)->withQueryString();

        return view('transactions.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.materialUsages', 'cashier']);
        return view('transactions.show', compact('sale'));
    }
}
