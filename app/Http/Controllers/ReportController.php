<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Apply date-range filters to a query.
     */
    private function applyFilters(Request $request, $query): array
    {
        $dateStart = $request->get('date_start');
        $dateEnd   = $request->get('date_end');

        if ($dateStart && $dateEnd) {
            $from = Carbon::parse($dateStart)->startOfDay();
            $to   = Carbon::parse($dateEnd)->endOfDay();
            $query->whereBetween('completed_at', [$from, $to]);
        } elseif ($dateStart) {
            $from = Carbon::parse($dateStart)->startOfDay();
            $query->where('completed_at', '>=', $from);
        } elseif ($dateEnd) {
            $to = Carbon::parse($dateEnd)->endOfDay();
            $query->where('completed_at', '<=', $to);
        }

        return [$dateStart, $dateEnd];
    }

    public function index(Request $request)
    {
        $query = Sale::completed()->with(['items', 'cashier']);

        [$dateStart, $dateEnd] = $this->applyFilters($request, $query);

        // Clone query untuk stats sebelum paginate
        $allSales = (clone $query)->get();

        // Summary stats
        $totalRevenue  = $allSales->sum('total');
        $totalCount    = $allSales->count();
        $avgRevenue    = $totalCount > 0 ? $totalRevenue / $totalCount : 0;

        // Bahan menipis/habis
        $lowMaterials = Material::where('is_active', true)
            ->whereRaw('current_stock <= minimum_stock')
            ->orderBy('current_stock')
            ->get();

        // Menu terlaris berdasarkan qty porsi
        $topMenus = [];
        foreach ($allSales as $sale) {
            foreach ($sale->items as $item) {
                $name = $item->menu_name_snapshot;
                $topMenus[$name] = ($topMenus[$name] ?? 0) + $item->quantity;
            }
        }
        arsort($topMenus);

        // Pagination untuk tabel ringkasan transaksi
        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50]) ? (int) $request->input('per_page', 10) : 10;

        $sales = $query->orderByDesc('completed_at')
            ->paginate($perPage);

        $sales->appends(array_filter([
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'per_page'   => $perPage,
        ]));

        return view('reports.index', compact(
            'sales', 'totalRevenue', 'totalCount', 'avgRevenue',
            'lowMaterials', 'topMenus', 'perPage', 'dateStart', 'dateEnd'
        ));
    }

    public function export(Request $request)
    {
        $query = Sale::completed()->with('items');

        [$dateStart, $dateEnd] = $this->applyFilters($request, $query);

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

        // Build descriptive filename
        $suffix = match (true) {
            $dateStart && $dateEnd => $dateStart . '_sd_' . $dateEnd,
            (bool) $dateStart      => 'dari_' . $dateStart,
            (bool) $dateEnd        => 'sd_' . $dateEnd,
            default                => 'semua_' . now()->format('Ymd-His'),
        };

        $filename = 'ringkasan-penjualan-' . $suffix . '.csv';

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
