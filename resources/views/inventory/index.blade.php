@extends('layouts.app')
@section('title', 'Bahan Baku & Stok')

@section('content')
<!--<div class="eyebrow">02 / Inventory Management</div>-->
<h1 class="title">Bahan Baku &amp; Stok</h1>
<p class="sub">Pantau stok saat ini, ambang minimum, dan riwayat perubahan yang berasal dari transaksi atau penyesuaian manual.</p>

{{-- ── Stat Cards ─────────────────────────────────────────────── --}}
<div class="grid stats">
  <div class="panel">
    <div class="stat-label">Total Bahan</div>
    <div class="stat-value">{{ $stats['total'] }}</div>
    <div class="sub">Bahan terdaftar</div>
  </div>
  <div class="panel">
    <div class="stat-label">Stok Aman</div>
    <div class="stat-value" style="color:var(--green)">{{ $stats['safe'] }}</div>
    <div class="sub">Di atas batas minimum</div>
  </div>
  <div class="panel">
    <div class="stat-label">Perlu Perhatian</div>
    <div class="stat-value" style="color:var(--amber)">{{ $stats['low'] }}</div>
    <div class="sub">Stok mencapai batas minimum</div>
  </div>
  <div class="panel">
    <div class="stat-label">Stok Habis</div>
    <div class="stat-value" style="color:var(--red)">{{ $stats['empty'] }}</div>
    <div class="sub">Perlu penanganan segera</div>
  </div>
</div>

{{-- ── Daftar Persediaan ──────────────────────────────────────── --}}
<div class="panel">
  <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:15px">
    <div style="font-weight:800;font-size:15px">Daftar Persediaan</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <form method="GET" action="{{ route('inventory.index') }}" style="display:flex;gap:8px;flex-wrap:wrap">
        <input name="search" placeholder="Cari bahan..." value="{{ request('search') }}" style="width:180px">
        <select name="status" onchange="this.form.submit()">
          <option value="">Semua status</option>
          @foreach(['Aman','Menipis','Habis'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn outline sm">Filter</button>
      </form>
      @permission('inventory.manage')
        <button class="btn sm" onclick="openModal('addMaterialModal')">+ Tambah Bahan</button>
      @endpermission
      @permission('inventory.adjust')
        <button class="btn secondary sm" onclick="openModal('adjustModal')">⇅ Sesuaikan Stok</button>
      @endpermission
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Bahan Baku</th>
          <th>Stok Saat Ini</th>
          <th>Minimum</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($materials as $mat)
          <tr>
            <td><b>{{ $mat->name }}</b></td>
            <td>{{ $mat->stock_formatted }} {{ $mat->unit }}</td>
            <td>{{ $mat->min_stock_formatted }} {{ $mat->unit }}</td>
            <td><span class="status {{ $mat->status_class }}">{{ $mat->status }}</span></td>
            <td style="display:flex;gap:6px;flex-wrap:wrap">
              @permission('inventory.adjust')
                <button class="btn secondary sm"
                  onclick="openAdjustFor({{ $mat->id }}, '{{ addslashes($mat->name) }}', '{{ $mat->unit }}')">
                  Sesuaikan
                </button>
              @endpermission
              @permission('inventory.manage')
                <button class="btn outline sm"
                  onclick="openEditMaterial({{ $mat->id }}, '{{ addslashes($mat->name) }}', '{{ $mat->unit }}', {{ $mat->minimum_stock }})">
                  Ubah
                </button>
                <form method="POST" action="{{ route('inventory.destroy', $mat) }}" onsubmit="return confirm('Nonaktifkan bahan ini?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn danger sm">Nonaktifkan</button>
                </form>
              @endpermission
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5">
              <div class="empty-state">
                <span class="material-symbols-outlined">inventory_2</span>
                Tidak ada bahan baku. Tambahkan bahan terlebih dahulu.
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $materials->links('vendor.pagination.custom') }}
</div>

{{-- ── Riwayat Pergerakan Stok ─────────────────────────────────── --}}
<div class="panel" style="margin-top:16px">
  <div style="font-weight:800;margin-bottom:12px;font-size:15px">Riwayat Pergerakan Stok</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Waktu</th>
          <th>Bahan</th>
          <th>Perubahan</th>
          <th>Sumber</th>
          <th>Catatan</th>
          <th>Oleh</th>
        </tr>
      </thead>
      <tbody>
        @forelse($movements as $mov)
          <tr>
            <td>{{ $mov->created_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
            <td><b>{{ $mov->material->name }}</b></td>
            <td style="font-weight:800;color:{{ (float)$mov->quantity_change >= 0 ? 'var(--green)' : 'var(--red)' }}">
              {{ (float)$mov->quantity_change >= 0 ? '+' : '' }}{{ $mov->change_formatted }} {{ $mov->material->unit }}
            </td>
            <td><span class="tag">{{ $mov->movement_type_label }}</span></td>
            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis">{{ $mov->notes ?? '-' }}</td>
            <td>{{ $mov->user?->name ?? 'Sistem' }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <span class="material-symbols-outlined">history</span>
                Belum ada pergerakan stok.
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $movements->links('vendor.pagination.custom') }}
</div>

{{-- ── Modal: Tambah Bahan ─────────────────────────────────────── --}}
@permission('inventory.manage')
<div id="addMaterialModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Tambah Bahan Baku</h2>
      <button class="btn secondary sm" onclick="closeModal('addMaterialModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('inventory.store') }}">
      @csrf
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label for="mat_name">Nama Bahan</label>
          <input id="mat_name" name="name" required placeholder="cth. Biji Kopi">
        </div>
        <div class="form-group">
          <label for="mat_unit">Satuan</label>
          <input id="mat_unit" name="unit" required placeholder="g / ml / buah">
        </div>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
        <div class="form-group">
          <label for="mat_stock">Stok Awal</label>
          <input id="mat_stock" name="current_stock" type="number" min="0" step="any" value="0" required>
        </div>
        <div class="form-group">
          <label for="mat_min">Stok Minimum</label>
          <input id="mat_min" name="minimum_stock" type="number" min="0" step="any" value="0" required>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px">
        <button type="button" class="btn secondary" onclick="closeModal('addMaterialModal')">Batal</button>
        <button type="submit" class="btn">Simpan Bahan</button>
      </div>
    </form>
  </div>
</div>

{{-- ── Modal: Edit Bahan ─────────────────────────────────────────── --}}
<div id="editMaterialModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Ubah Bahan Baku</h2>
      <button class="btn secondary sm" onclick="closeModal('editMaterialModal')">✕</button>
    </div>
    <form method="POST" id="editMaterialForm">
      @csrf @method('PUT')
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px">
        <div class="form-group">
          <label>Nama Bahan</label>
          <input id="edit_mat_name" name="name" required>
        </div>
        <div class="form-group">
          <label>Satuan</label>
          <input id="edit_mat_unit" name="unit" required>
        </div>
      </div>
      <div class="form-group" style="margin-top:12px">
        <label>Stok Minimum</label>
        <input id="edit_mat_min" name="minimum_stock" type="number" min="0" step="any" required>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px">
        <button type="button" class="btn secondary" onclick="closeModal('editMaterialModal')">Batal</button>
        <button type="submit" class="btn">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
@endpermission

{{-- ── Modal: Penyesuaian Stok ─────────────────────────────────── --}}
@permission('inventory.adjust')
<div id="adjustModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
      <h2 class="modal-title">Penyesuaian Stok</h2>
      <button class="btn secondary sm" onclick="closeModal('adjustModal')">✕</button>
    </div>
    <p class="sub">Catat stok masuk, koreksi keluar, atau sesuaikan ke stok fisik.</p>

    <form method="POST" action="{{ route('inventory.adjust') }}" style="margin-top:18px">
      @csrf
      <div class="form-group" style="margin-bottom:12px">
        <label>Bahan Baku</label>
        <select id="adjustMaterialSel" name="material_id" required>
          @foreach($allMaterials as $mat)
            <option value="{{ $mat->id }}">{{ $mat->name }} (stok: {{ $mat->stock_formatted }} {{ $mat->unit }})</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Jenis Penyesuaian</label>
        <select name="type" required>
          <option value="in">Stok masuk (+)</option>
          <option value="set">Sesuaikan ke stok fisik (=)</option>
          <option value="out">Pengeluaran / koreksi (−)</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Jumlah / Stok Fisik</label>
        <input name="quantity" type="number" min="0" step="any" value="1" required>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Catatan</label>
        <input name="note" placeholder="cth. penerimaan bahan / hasil pengecekan">
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('adjustModal')">Batal</button>
        <button type="submit" class="btn">Simpan Penyesuaian</button>
      </div>
    </form>
  </div>
</div>
@endpermission

@endsection

@push('scripts')
<script>
function openAdjustFor(id, name, unit) {
  const sel = document.getElementById('adjustMaterialSel');
  if (sel) sel.value = id;
  openModal('adjustModal');
}

function openEditMaterial(id, name, unit, minStock) {
  document.getElementById('editMaterialForm').action = `/inventory/${id}`;
  document.getElementById('edit_mat_name').value = name;
  document.getElementById('edit_mat_unit').value = unit;
  document.getElementById('edit_mat_min').value  = minStock;
  openModal('editMaterialModal');
}
</script>
@endpush
