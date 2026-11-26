<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class TransactionController extends Controller
{
    private function applyFilters(Request $request, $query): array
    {
        $dateStart = $request->get('date_start');
        $dateEnd   = $request->get('date_end');

        if ($dateStart && $dateEnd) {
            $query->whereBetween('completed_at', [
                Carbon::parse($dateStart)->startOfDay(),
                Carbon::parse($dateEnd)->endOfDay(),
            ]);
        } elseif ($dateStart) {
            $query->where('completed_at', '>=', Carbon::parse($dateStart)->startOfDay());
        } elseif ($dateEnd) {
            $query->where('completed_at', '<=', Carbon::parse($dateEnd)->endOfDay());
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('items', fn($qi) => $qi->where('menu_name_snapshot', 'like', "%{$search}%"));
            });
        }

        return [$dateStart, $dateEnd];
    }

    public function index(Request $request)
    {
        $query = Sale::completed()->with(['items', 'cashier']);

        [$dateStart, $dateEnd] = $this->applyFilters($request, $query);

        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50]) ? (int) $request->input('per_page', 10) : 10;

        $sales = $query->orderByDesc('completed_at')
            ->paginate($perPage);

        $sales->appends(array_filter([
            'search'     => $request->input('search'),
            'date_start' => $dateStart,
            'date_end'   => $dateEnd,
            'per_page'   => $perPage,
        ]));

        return view('transactions.index', compact('sales', 'perPage', 'dateStart', 'dateEnd'));
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

        $suffix = match (true) {
            $dateStart && $dateEnd => $dateStart . '_sd_' . $dateEnd,
            (bool) $dateStart      => 'dari_' . $dateStart,
            (bool) $dateEnd        => 'sd_' . $dateEnd,
            default                => 'semua_' . now()->format('Ymd-His'),
        };

        $filename = 'riwayat-transaksi-' . $suffix . '.csv';

        return Response::make("\xEF\xBB\xBF" . $csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function show(Sale $sale)
    {
        $sale->load(['items.materialUsages', 'cashier']);
        return view('transactions.show', compact('sale'));
    }
}
