<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Category;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PosController extends Controller
{
    public function __construct(private CheckoutService $checkoutService) {}

    public function index()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $menus      = Menu::with(['category', 'recipeItems.material'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('categories', 'menus'));
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.menu_id'  => 'required|integer|exists:menus,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:cash,qris',
            'notes'          => 'nullable|string|max:500',
        ]);

        try {
            $sale = $this->checkoutService->processCheckout(
                cartItems:     $validated['items'],
                paymentMethod: $validated['payment_method'],
                cashierId:     auth()->id(),
                notes:         $validated['notes'] ?? null
            );

            return response()->json([
                'success'          => true,
                'message'          => 'Transaksi ' . $sale->transaction_code . ' berhasil. Stok bahan diperbarui.',
                'transaction_code' => $sale->transaction_code,
                'total'            => $sale->total_formatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
