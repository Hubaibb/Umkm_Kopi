@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<div class="eyebrow">05 / Transaction History</div>
<h1 class="title">Riwayat Transaksi</h1>
<p class="sub">Lihat detail transaksi yang berhasil dan bahan yang dikurangi berdasarkan BOM.</p>

<div class="panel" style="margin-top:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px">
    <div style="font-weight:800;font-size:15px">Transaksi Berhasil</div>
    <form method="GET" action="{{ route('transactions.index') }}">
      <input name="search" value="{{ request('search') }}" placeholder="Cari ID / nama menu..." style="width:240px">
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID Transaksi</th>
          <th>Waktu</th>
          <th>Item</th>
          <th>Kasir</th>
          <th>Metode</th>
          <th>Total</th>
          <th>Detail</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td><b>{{ $sale->transaction_code }}</b></td>
            <td>{{ $sale->completed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
            <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis">{{ $sale->items_summary }}</td>
            <td>{{ $sale->cashier?->name ?? '-' }}</td>
            <td><span class="tag">{{ $sale->payment_label }}</span></td>
            <td><b>{{ $sale->total_formatted }}</b></td>
            <td>
              <a href="{{ route('transactions.show', $sale) }}" class="btn secondary sm">
                <span class="material-symbols-outlined" style="font-size:15px">open_in_new</span>
                Detail
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <span class="material-symbols-outlined">receipt_long</span>
                Belum ada transaksi berhasil.
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $sales->links('vendor.pagination.custom') }}
</div>
@endsection
