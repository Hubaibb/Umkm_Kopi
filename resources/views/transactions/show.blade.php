@extends('layouts.app')
@section('title', 'Detail Transaksi ' . $sale->transaction_code)

@section('content')
<div class="eyebrow">05 / Transaction History</div>
<div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
  <a href="{{ route('transactions.index') }}" class="btn secondary sm">← Kembali</a>
  <h1 class="title" style="margin:0">{{ $sale->transaction_code }}</h1>
</div>

<div class="split" style="margin-top:18px">
  {{-- ── Info Transaksi ─────────────────────────────────────── --}}
  <div>
    <div class="panel">
      <div style="font-weight:800;font-size:15px;margin-bottom:14px">Informasi Transaksi</div>
      <table style="font-size:13px">
        <tr>
          <td style="color:var(--muted);padding:6px 0;width:140px">Kode Transaksi</td>
          <td><b>{{ $sale->transaction_code }}</b></td>
        </tr>
        <tr>
          <td style="color:var(--muted);padding:6px 0">Waktu</td>
          <td>{{ $sale->completed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
          <td style="color:var(--muted);padding:6px 0">Kasir</td>
          <td>{{ $sale->cashier?->name ?? '-' }}</td>
        </tr>
        <tr>
          <td style="color:var(--muted);padding:6px 0">Metode Bayar</td>
          <td><span class="tag">{{ $sale->payment_label }}</span></td>
        </tr>
        <tr>
          <td style="color:var(--muted);padding:6px 0">Status</td>
          <td><span class="status safe">Berhasil</span></td>
        </tr>
        <tr>
          <td style="color:var(--muted);padding:6px 0">Total</td>
          <td style="font-size:18px;font-weight:800;color:var(--coffee)">{{ $sale->total_formatted }}</td>
        </tr>
        @if($sale->notes)
        <tr>
          <td style="color:var(--muted);padding:6px 0">Catatan</td>
          <td>{{ $sale->notes }}</td>
        </tr>
        @endif
      </table>
    </div>

    {{-- Item Pesanan --}}
    <div class="panel" style="margin-top:14px">
      <div style="font-weight:800;font-size:15px;margin-bottom:12px">Item Pesanan</div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Menu</th>
              <th>Harga Satuan</th>
              <th>Qty</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            @foreach($sale->items as $item)
              <tr>
                <td><b>{{ $item->menu_name_snapshot }}</b></td>
                <td>{{ $item->unit_price_formatted }}</td>
                <td>{{ $item->quantity }}</td>
                <td><b>{{ $item->line_total_formatted }}</b></td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr style="background:#fbf6f1">
              <td colspan="3" style="text-align:right;font-weight:700;padding:13px 12px">Total</td>
              <td style="font-weight:800;font-size:15px;padding:13px 12px">{{ $sale->total_formatted }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  {{-- ── Rincian Bahan Terpakai ──────────────────────────────── --}}
  <div class="panel">
    <div style="font-weight:800;font-size:15px;margin-bottom:12px">Bahan yang Digunakan (BOM)</div>
    <p class="sub" style="margin-bottom:14px">Total pemakaian bahan dari semua item transaksi ini.</p>

    @php
      // Agregasi pemakaian bahan per material
      $usageSummary = [];
      foreach($sale->items as $item) {
        foreach($item->materialUsages as $usage) {
          $key = $usage->material_name_snapshot;
          if (!isset($usageSummary[$key])) {
            $usageSummary[$key] = ['name' => $usage->material_name_snapshot, 'unit' => $usage->unit_snapshot, 'qty' => 0];
          }
          $usageSummary[$key]['qty'] += (float)$usage->quantity_used;
        }
      }
    @endphp

    @if(count($usageSummary) > 0)
      @foreach($usageSummary as $usage)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0e8e2">
          <div>
            <b style="font-size:13px">{{ $usage['name'] }}</b>
          </div>
          <div style="font-weight:700;color:var(--red)">
            −{{ rtrim(rtrim(number_format($usage['qty'], 3, ',', '.'), '0'), ',') }} {{ $usage['unit'] }}
          </div>
        </div>
      @endforeach
    @else
      <div class="empty-state">
        <span class="material-symbols-outlined">inventory_2</span>
        Tidak ada data pemakaian bahan.
      </div>
    @endif

    {{-- Per Item Detail --}}
    <div style="margin-top:20px">
      <div style="font-weight:700;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Rincian per Item</div>
      @foreach($sale->items as $item)
        <div style="padding:10px 0;border-bottom:1px solid #f0e8e2">
          <div style="font-weight:700;font-size:13px;margin-bottom:6px">{{ $item->menu_name_snapshot }} × {{ $item->quantity }}</div>
          @foreach($item->materialUsages as $usage)
            <div style="font-size:12px;color:var(--muted);padding:2px 0 2px 12px">
              {{ $usage->material_name_snapshot }}:
              <b>{{ rtrim(rtrim(number_format((float)$usage->quantity_used, 3, ',', '.'), '0'), ',') }} {{ $usage->unit_snapshot }}</b>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
