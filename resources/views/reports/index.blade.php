@extends('layouts.app')
@section('title', 'Analitik Penjualan')

@section('content')
<!--<div class="eyebrow">04 / Sales Analytics</div>-->
<h1 class="title">Analitik Penjualan &amp; Operasional</h1>
<p class="sub">Ringkasan dihitung dari transaksi berhasil di database. Semua angka real-time.</p>

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
        <div class="stock-row">
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
<div class="panel" id="ringkasan-transaksi" style="margin-top:16px">
  <div style="font-weight:800;font-size:15px;margin-bottom:14px">Ringkasan Transaksi</div>

  {{-- Filter + per-page row --}}
  <div class="report-controls">
    <form method="GET" action="{{ route('reports.index') }}#ringkasan-transaksi" id="filterForm" class="filter-form">
      <div class="date-input-wrap">
        <label for="date_start">Dari</label>
        <input type="date" id="date_start" name="date_start" value="{{ $dateStart ?? '' }}" max="{{ date('Y-m-d') }}">
      </div>
      <span class="date-separator">–</span>
      <div class="date-input-wrap">
        <label for="date_end">Sampai</label>
        <input type="date" id="date_end" name="date_end" value="{{ $dateEnd ?? '' }}" max="{{ date('Y-m-d') }}">
      </div>
      <button type="submit" class="btn sm filter-action-btn">
        <span class="material-symbols-outlined" style="font-size:15px">filter_alt</span>
        Terapkan
      </button>
    </form>

    @permission('reports.export')
      <a id="exportBtn" href="{{ route('reports.export', array_filter(['date_start' => $dateStart ?? null, 'date_end' => $dateEnd ?? null])) }}" class="btn outline sm filter-action-btn">
        <span class="material-symbols-outlined" style="font-size:16px">download</span>
        Unduh CSV
      </a>
    @endpermission

    <div class="report-perpage">
      <span class="perpage-label">Tampilkan</span>
      <form method="GET" action="{{ route('reports.index') }}#ringkasan-transaksi">
        @if($dateStart)<input type="hidden" name="date_start" value="{{ $dateStart }}">@endif
        @if($dateEnd)<input type="hidden" name="date_end" value="{{ $dateEnd }}">@endif
        <select name="per_page" class="perpage-select" onchange="this.form.submit()">
          @foreach([10, 25, 50] as $pp)
            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
          @endforeach
        </select>
      </form>
      <span class="perpage-label">data</span>
    </div>
  </div>

  {{-- Active filter badge --}}
  @if($dateStart || $dateEnd)
    <div class="active-filter-badge" style="margin:10px 0">
      <span class="material-symbols-outlined" style="font-size:14px">date_range</span>
      @if($dateStart && $dateEnd)
        {{ \Carbon\Carbon::parse($dateStart)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
      @elseif($dateStart)
        Dari {{ \Carbon\Carbon::parse($dateStart)->format('d M Y') }}
      @else
        Sampai {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
      @endif
      <a href="{{ route('reports.index') }}#ringkasan-transaksi" class="filter-clear" title="Hapus filter">&times;</a>
    </div>
  @endif

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Waktu</th>
          <th class="hide-mobile">Item</th>
          <th class="hide-mobile">Kasir</th>
          <th>Pembayaran</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sales as $sale)
          <tr>
            <td><b>{{ $sale->transaction_code }}</b></td>
            <td>{{ $sale->completed_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
            <td class="hide-mobile" style="max-width:260px;overflow:hidden;text-overflow:ellipsis">{{ $sale->items_summary }}</td>
            <td class="hide-mobile">{{ $sale->cashier?->name ?? '-' }}</td>
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
  {{ $sales->links('vendor.pagination.custom', ['anchor' => '#ringkasan-transaksi']) }}
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const dateStart = document.getElementById('date_start');
  const dateEnd   = document.getElementById('date_end');

  function updateExportLink() {
    const exportBtn = document.getElementById('exportBtn');
    if (!exportBtn) return;

    const params = new URLSearchParams();
    if (dateStart.value) params.set('date_start', dateStart.value);
    if (dateEnd.value) params.set('date_end', dateEnd.value);

    const qs = params.toString();
    exportBtn.href = '{{ route("reports.export") }}' + (qs ? '?' + qs : '');
  }

  if (dateStart) dateStart.addEventListener('change', updateExportLink);
  if (dateEnd) dateEnd.addEventListener('change', updateExportLink);
});
</script>
@endpush
@endsection
