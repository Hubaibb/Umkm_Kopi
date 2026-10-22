<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'UMKM Kopi BPK RI') — SuKopi</title>
<meta name="description" content="Sistem Operasional Terintegrasi UMKM Kopi BPK RI — Koperasi BPK RI">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
@stack('styles')
</head>
<body>

{{-- ── Sidebar ──────────────────────────────────────────────── --}}
<aside class="sidebar">
  <div class="brand">
    <div class="brand-icon">
      <span class="material-symbols-outlined">local_cafe</span>
    </div>
    <div>
      <div class="brand-name">UMKM Kopi BPK RI</div>
      <div class="brand-sub">Koperasi BPK RI</div>
    </div>
  </div>

  <div class="eyebrow" style="padding: 0 12px 8px">Workspace</div>

  <nav>
    @permission('pos.create')
    <a href="{{ route('pos.index') }}"
       class="navbtn {{ request()->routeIs('pos.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">point_of_sale</span>POS / Transaksi
    </a>
    @endpermission

    @permission('inventory.view')
    <a href="{{ route('inventory.index') }}"
       class="navbtn {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">inventory_2</span>Bahan Baku &amp; Stok
    </a>
    @endpermission

    @permission('bom.view')
    <a href="{{ route('bom.index') }}"
       class="navbtn {{ request()->routeIs('bom.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">menu_book</span>Resep / BOM
    </a>
    @endpermission

    @permission('reports.view')
    <a href="{{ route('reports.index') }}"
       class="navbtn {{ request()->routeIs('reports.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">monitoring</span>Analitik Penjualan
    </a>
    @endpermission

    @permission('transactions.view')
    <a href="{{ route('transactions.index') }}"
       class="navbtn {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">receipt_long</span>Riwayat Transaksi
    </a>
    @endpermission

    @permission('accounts.view')
    <a href="{{ route('accounts.index') }}"
       class="navbtn {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
      <span class="material-symbols-outlined">manage_accounts</span>Akun
    </a>
    @endpermission
  </nav>

  <div style="margin-top: auto; padding: 16px 12px 0">
    <div style="font-size: 10px; color: #948378; text-align: center; padding-top: 12px; border-top: 1px solid var(--line)">
      CAPSTONE · UMKM Kopi BPK RI
    </div>
  </div>
</aside>

{{-- ── Shell ─────────────────────────────────────────────────── --}}
<div class="shell">

  {{-- Topbar --}}
  <header class="topbar">
    <div>
      <div class="eyebrow">Platform Operasional Terintegrasi</div>
      <div style="font-size: 13px; font-weight: 800; margin-top: 3px; color: #241b16;">
        Kedai Kopi · Koperasi BPK RI
      </div>
    </div>

    <div class="user-menu">
      <div style="text-align: right; display: none;" class="user-info-text">
        <div style="font-size: 13px; font-weight: 700;">{{ auth()->user()->name }}</div>
        <div style="font-size: 11px; color: var(--muted);">{{ auth()->user()->role->name }}</div>
      </div>
      <div class="user-avatar" title="{{ auth()->user()->name }}">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
      </div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn outline sm" title="Keluar">
          <span class="material-symbols-outlined" style="font-size:16px">logout</span>
          Keluar
        </button>
      </form>
    </div>
  </header>

  {{-- Main content --}}
  <main class="content">
    {{-- Flash messages --}}
    @if(session('success'))
      <div class="alert-success" style="margin-bottom: 18px" id="flash-msg">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px">check_circle</span>
        {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="alert-error" style="margin-bottom: 18px">
        <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;margin-right:6px">error</span>
        @foreach($errors->all() as $err)
          {{ $err }}<br>
        @endforeach
      </div>
    @endif

    @yield('content')
  </main>
</div>

{{-- Toast notification --}}
<div id="toast" class="toast" role="alert"></div>

{{-- Global JS helpers --}}
<script>
const rupiah = n => 'Rp' + Math.round(n).toLocaleString('id-ID');
const fmtQty = n => {
  let s = parseFloat(n).toFixed(3);
  s = s.replace(/\.?0+$/, '');
  return s.replace('.', ',');
};

function showToast(msg, type = '') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show' + (type ? ' ' + type : '');
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => t.classList.remove('show'), 3500);
}

// Auto-dismiss flash messages
const flash = document.getElementById('flash-msg');
if (flash) setTimeout(() => flash.style.display = 'none', 5000);

// CSRF token for fetch
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

// Modal helpers
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-bg.show').forEach(m => m.classList.remove('show'));
});
</script>

@stack('scripts')
</body>
</html>
