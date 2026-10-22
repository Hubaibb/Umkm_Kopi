<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'all');

        $query = Sale::completed()->with(['items', 'cashier']);

        if ($period === 'today') {
            $query->today();
        }

        $sales = $query->orderByDesc('completed_at')->get();

        // Summary stats
        $totalRevenue  = $sales->sum('total');
        $totalCount    = $sales->count();
        $avgRevenue    = $totalCount > 0 ? $totalRevenue / $totalCount : 0;

        // Bahan menipis/habis
        $lowMaterials = Material::where('is_active', true)
            ->whereRaw('current_stock <= minimum_stock')
            ->orderBy('current_stock')
            ->get();

        // Menu terlaris berdasarkan qty porsi
        $topMenus = [];
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $name = $item->menu_name_snapshot;
                $topMenus[$name] = ($topMenus[$name] ?? 0) + $item->quantity;
            }
        }
        arsort($topMenus);

        return view('reports.index', compact(
            'sales', 'period', 'totalRevenue', 'totalCount', 'avgRevenue',
            'lowMaterials', 'topMenus'
        ));
    }

    public function export(Request $request)
    {
        $period = $request->get('period', 'all');

        $query = Sale::completed()->with('items');

        if ($period === 'today') {
            $query->today();
        }

        $sales = $query->orderByDesc('completed_at')->get();

        $rows = [['ID Transaksi', 'Waktu', 'Kasir', 'Item', 'Metode Pembayaran', 'Total']];

        foreach ($sales as $sale) {
            $itemsSummary = $sale->items
                ->map(fn($i) => $i->menu_name_snapshot . ' × ' . $i->quantity)
                ->implode('; ');

            $rows[] = [
                $sale->transaction_code,
                $sale->completed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'),
                $sale->cashier?->name ?? '-',
                $itemsSummary,
                $sale->payment_label,
                (float) $sale->total,
            ];
        }

        $csv = collect($rows)
            ->map(fn($row) => implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)))
            ->implode("\n");

        $filename = 'ringkasan-penjualan-' . now()->format('Ymd-His') . '.csv';

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
