@extends('layouts.app')
@section('title', 'Riwayat Transaksi')

@section('content')
<!--<div class="eyebrow">05 / Transaction History</div>-->
<h1 class="title">Riwayat Transaksi</h1>
<p class="sub">Lihat detail transaksi yang berhasil dan bahan yang dikurangi berdasarkan BOM.</p>

<div class="panel" id="ringkasan-transaksi" style="margin-top:18px">

  {{-- ── Filter Row ──────────────────────────────────────────── --}}
  <div class="trx-toolbar">
    <div style="font-weight:800;font-size:15px">Ringkasan Transaksi</div>

    <div class="trx-toolbar-actions">
      {{-- Date range --}}
      <form method="GET" action="{{ route('transactions.index') }}#ringkasan-transaksi" id="trxFilterForm" class="filter-form">
        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
        @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
        <div class="date-input-wrap">
          <label for="trx_date_start">Dari</label>
          <input type="date" id="trx_date_start" name="date_start" value="{{ $dateStart ?? '' }}" max="{{ date('Y-m-d') }}">
        </div>
        <span class="date-separator">–</span>
        <div class="date-input-wrap">
          <label for="trx_date_end">Sampai</label>
          <input type="date" id="trx_date_end" name="date_end" value="{{ $dateEnd ?? '' }}" max="{{ date('Y-m-d') }}">
        </div>
        <button type="submit" class="btn sm filter-action-btn">
          <span class="material-symbols-outlined" style="font-size:15px">filter_alt</span>
          Terapkan
        </button>
      </form>

      {{-- Export --}}
      <a id="trxExportBtn" href="{{ route('transactions.export', array_filter(['date_start' => $dateStart ?? null, 'date_end' => $dateEnd ?? null, 'search' => request('search')])) }}" class="btn outline sm filter-action-btn">
        <span class="material-symbols-outlined" style="font-size:16px">download</span>
        Unduh CSV
      </a>
    </div>
  </div>

  {{-- Active filter badge --}}
  @if(($dateStart ?? null) || ($dateEnd ?? null))
    <div class="active-filter-badge" style="margin-bottom:12px">
      <span class="material-symbols-outlined" style="font-size:14px">date_range</span>
      @if($dateStart && $dateEnd)
        {{ \Carbon\Carbon::parse($dateStart)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
      @elseif($dateStart)
        Dari {{ \Carbon\Carbon::parse($dateStart)->format('d M Y') }}
      @else
        Sampai {{ \Carbon\Carbon::parse($dateEnd)->format('d M Y') }}
      @endif
      <a href="{{ route('transactions.index', array_filter(['search' => request('search'), 'per_page' => request('per_page')])) }}" class="filter-clear" title="Hapus filter">&times;</a>
    </div>
  @endif

  {{-- ── Controls row (search + per-page) ────────────────────── --}}
  <div class="trx-controls">
    <form method="GET" action="{{ route('transactions.index') }}#ringkasan-transaksi" class="trx-search-form">
      @if(request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
      @if($dateStart ?? null)<input type="hidden" name="date_start" value="{{ $dateStart }}">@endif
      @if($dateEnd ?? null)<input type="hidden" name="date_end" value="{{ $dateEnd }}">@endif
      <input name="search" value="{{ request('search') }}" placeholder="Cari ID / nama menu..." class="trx-search-input">
    </form>

    <div style="display:flex;align-items:center;gap:8px;font-size:13px;color:#666">
      <span class="perpage-label">Tampilkan</span>
      <form method="GET" action="{{ route('transactions.index') }}#ringkasan-transaksi" id="trxPerPageForm">
        @if(request('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
        @if($dateStart ?? null)<input type="hidden" name="date_start" value="{{ $dateStart }}">@endif
        @if($dateEnd ?? null)<input type="hidden" name="date_end" value="{{ $dateEnd }}">@endif
        <select name="per_page" class="perpage-select" onchange="this.form.submit()">
          @foreach([10, 25, 50] as $pp)
            <option value="{{ $pp }}" {{ $perPage == $pp ? 'selected' : '' }}>{{ $pp }}</option>
          @endforeach
        </select>
      </form>
      <span class="perpage-label">data</span>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID Transaksi</th>
          <th>Waktu</th>
          <th class="hide-mobile">Item</th>
          <th class="hide-mobile">Kasir</th>
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
            <td class="hide-mobile" style="max-width:240px;overflow:hidden;text-overflow:ellipsis">{{ $sale->items_summary }}</td>
            <td class="hide-mobile">{{ $sale->cashier?->name ?? '-' }}</td>
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
  {{ $sales->links('vendor.pagination.custom', ['anchor' => '#ringkasan-transaksi']) }}
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const ds = document.getElementById('trx_date_start');
  const de = document.getElementById('trx_date_end');

  function updateExport() {
    const btn = document.getElementById('trxExportBtn');
    if (!btn) return;
    const p = new URLSearchParams();
    if (ds.value) p.set('date_start', ds.value);
    if (de.value) p.set('date_end', de.value);
    const search = '{{ request("search") }}';
    if (search) p.set('search', search);
    const qs = p.toString();
    btn.href = '{{ route("transactions.export") }}' + (qs ? '?' + qs : '');
  }

  ds.addEventListener('change', updateExport);
  de.addEventListener('change', updateExport);
});
</script>
@endpush
@endsection
