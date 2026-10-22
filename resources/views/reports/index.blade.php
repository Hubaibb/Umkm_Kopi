@extends('layouts.app')
@section('title', 'Analitik Penjualan')

@section('content')
<div class="eyebrow">04 / Sales Analytics</div>
<h1 class="title">Analitik Penjualan &amp; Operasional</h1>
<p class="sub">Ringkasan dihitung dari transaksi berhasil di database. Semua angka real-time.</p>

<div style="display:flex;gap:10px;flex-wrap:wrap;margin:18px 0;align-items:center">
  <form method="GET" action="{{ route('reports.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <select name="period" onchange="this.form.submit()" style="width:auto">
      <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Semua transaksi</option>
      <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Transaksi hari ini</option>
    </select>
  </form>
  @permission('reports.export')
    <a href="{{ route('reports.export', ['period' => $period]) }}" class="btn outline sm">
      <span class="material-symbols-outlined" style="font-size:16px">download</span>
      Unduh CSV
    </a>
  @endpermission
</div>

{{-- ── Stats ──────────────────────────────────────────────────── --}}
<div class="grid stats">
  <div class="panel">
    <div class="stat-label">Total Penjualan</div>
    <div class="stat-value">Rp{{ number_format($totalRevenue, 0, ',', '.') }}</div>
    <div class="sub">Transaksi sukses</div>
  </div>
  <div class="panel">
    <div class="stat-label">Transaksi</div>
    <div class="stat-value">{{ $totalCount }}</div>
    <div class="sub">Jumlah transaksi berhasil</div>
  </div>
  <div class="panel">
    <div class="stat-label">Rata-rata Transaksi</div>
    <div class="stat-value">Rp{{ number_format($avgRevenue, 0, ',', '.') }}</div>
    <div class="sub">Penjualan ÷ transaksi</div>
  </div>
  <div class="panel">
    <div class="stat-label">Bahan Menipis/Habis</div>
    <div class="stat-value" style="color:{{ $lowMaterials->count() > 0 ? 'var(--red)' : 'var(--green)' }}">
      {{ $lowMaterials->count() }}
    </div>
    <div class="sub">Berdasarkan stok terkini</div>
  </div>
</div>

<div class="split">
  {{-- ── Menu Terlaris ──────────────────────────────────────── --}}
  <div class="panel">
    <div style="font-weight:800">Menu Terlaris</div>
    <div class="sub">Berdasarkan jumlah porsi dari transaksi sukses</div>
    <div style="margin-top:16px">
      @if(count($topMenus) > 0)
        @php $maxQty = max($topMenus); @endphp
        @foreach($topMenus as $name => $qty)
          <div class="bar-row">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $name }}</span>
            <div class="bar-track">
              <div class="bar-fill" style="width:{{ $maxQty > 0 ? round($qty/$maxQty*100) : 0 }}%"></div>
            </div>
            <b style="text-align:right;font-size:11px">{{ $qty }} porsi</b>
          </div>
        @endforeach
      @else
        <div class="empty-state">
          <span class="material-symbols-outlined">bar_chart</span>
          Belum ada transaksi untuk periode ini.
        </div>
      @endif
    </div>
  </div>

  {{-- ── Perhatian Stok ─────────────────────────────────────── --}}
  <div class="panel">
    <div style="font-weight:800">Perhatian Stok</div>
    <div class="sub">Status dihitung dari stok saat ini vs minimum</div>
    <div style="margin-top:16px">
      @forelse($lowMaterials as $mat)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0e8e2">
          <div>
            <b style="font-size:12px">{{ $mat->name }}</b>
            <div class="sub">{{ $mat->stock_formatted }} {{ $mat->unit }} • min. {{ $mat->min_stock_formatted }} {{ $mat->unit }}</div>
          </div>
          <span class="status {{ $mat->status_class }}">{{ $mat->status }}</span>
        </div>
      @empty
        <div class="empty-state">
          <span class="material-symbols-outlined">check_circle</span>
          Semua bahan berada di atas ambang minimum.
        </div>
      @endforelse
    </div>
  </div>
</div>

{{-- ── Ringkasan Transaksi ─────────────────────────────────────── --}}
<div class="panel" style="margin-top:16px">
  <div style="font-weight:800;margin-bottom:12px;font-size:15px">Ringkasan Transaksi</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Waktu</th>
          <th>Item</th>
          <th>Kasir</th>
          <th>Pembayaran</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td><b>{{ $sale->transaction_code }}</b></td>
            <td>{{ $sale->completed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
            <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis">{{ $sale->items_summary }}</td>
            <td>{{ $sale->cashier?->name ?? '-' }}</td>
            <td><span class="tag">{{ $sale->payment_label }}</span></td>
            <td><b>{{ $sale->total_formatted }}</b></td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <span class="material-symbols-outlined">receipt_long</span>
                Belum ada transaksi untuk periode ini.
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
