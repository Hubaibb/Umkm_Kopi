@extends('layouts.app')
@section('title', 'Resep / BOM')

@section('content')
<!--<div class="eyebrow">03 / Bill of Materials</div>-->
<h1 class="title">Resep &amp; Takaran Bahan (BOM)</h1>
<p class="sub">Setiap menu memiliki komposisi bahan. Saat penjualan berhasil, kebutuhan bahan dihitung dari takaran per porsi × jumlah porsi terjual.</p>

<div class="note" style="margin:18px 0">
  BOM di bawah adalah <b>data yang dapat diubah</b>. Takaran aktual harus divalidasi dengan standar racikan barista sebelum digunakan untuk stok nyata.
</div>

{{-- ── Toolbar: Tambah Menu / Kategori ─────────────────────────── --}}
@permission('menu.manage')
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
  <button class="btn sm" onclick="openModal('addMenuModal')">+ Tambah Menu</button>
  <button class="btn secondary sm" onclick="openModal('addCategoryModal')">+ Tambah Kategori</button>
</div>
@endpermission

{{-- ── Komposisi per Menu ──────────────────────────────────────── --}}
<div class="panel">
  <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
    <div style="font-weight:800;font-size:15px">Komposisi per Menu</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <select id="menuSelect" onchange="location.href='{{ route('bom.index') }}?menu_id='+this.value">
        @foreach($menus as $menu)
          <option value="{{ $menu->id }}" {{ $selectedMenu?->id === $menu->id ? 'selected' : '' }}>
            {{ $menu->name }}
          </option>
        @endforeach
      </select>
      @permission('bom.manage')
        @if($selectedMenu)
          <button class="btn sm" onclick="openModal('addBomModal')">+ Tambah Bahan</button>
        @endif
      @endpermission
      @permission('menu.manage')
        @if($selectedMenu)
          <button class="btn secondary sm" onclick="openEditMenuModal()">✏ Ubah Menu</button>
          <form method="POST" action="{{ route('bom.menus.destroy', $selectedMenu) }}" onsubmit="return confirm('Nonaktifkan menu ini?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn danger sm">Nonaktifkan Menu</button>
          </form>
        @endif
      @endpermission
    </div>
  </div>

  @if($selectedMenu)
    {{-- Menu info --}}
    <div style="display:flex;gap:12px;align-items:center;margin-bottom:14px;flex-wrap:wrap">
      <span class="tag">{{ $selectedMenu->category->name }}</span>
      <div>
        <b>{{ $selectedMenu->name }}</b>
        <div class="sub">Harga jual: <b>{{ $selectedMenu->price_formatted }}</b> / porsi</div>
      </div>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Bahan</th>
            <th>Takaran per Porsi</th>
            <th>Stok Tersedia</th>
            <th>Perkiraan Setelah 1 Porsi</th>
            @permission('bom.manage')<th>Aksi</th>@endpermission
          </tr>
        </thead>
        <tbody>
          @forelse($selectedMenu->recipeItems as $ri)
            <tr>
              <td><b>{{ $ri->material->name }}</b></td>
              <td>{{ $ri->qty_formatted }} {{ $ri->material->unit }}</td>
              <td>{{ $ri->material->stock_formatted }} {{ $ri->material->unit }}</td>
              <td style="color:{{ $ri->stock_after_one_serving < 0 ? 'var(--red)' : 'inherit' }}">
                {{ number_format($ri->stock_after_one_serving, 3, ',', '.') }} {{ $ri->material->unit }}
              </td>
              @permission('bom.manage')
              <td style="display:flex;gap:6px">
                <button class="btn secondary sm"
                  onclick="openEditBom({{ $ri->id }}, {{ (float)$ri->quantity_per_serving }}, '{{ addslashes($ri->material->name) }}')">
                  Ubah Takaran
                </button>
                <form method="POST" action="{{ route('bom.items.destroy', $ri) }}" onsubmit="return confirm('Hapus bahan ini dari resep?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn danger sm">Hapus</button>
                </form>
              </td>
              @endpermission
            </tr>
          @empty
            <tr>
              <td colspan="5">
                <div class="empty-state">
                  <span class="material-symbols-outlined">menu_book</span>
                  Belum ada bahan dalam resep ini.
                  @permission('bom.manage')
                    <br><button class="btn sm" style="margin-top:10px" onclick="openModal('addBomModal')">+ Tambah Bahan</button>
                  @endpermission
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @else
    <div class="empty-state">
      <span class="material-symbols-outlined">menu_book</span>
      Pilih atau tambahkan menu untuk melihat resep.
    </div>
  @endif
</div>

{{-- ── Simulasi Kebutuhan Bahan ─────────────────────────────────── --}}
<div class="panel" style="margin-top:16px">
  <div style="font-weight:800;margin-bottom:6px;font-size:15px">Simulasi Kebutuhan Bahan</div>
  <p class="sub">Pilih menu dan jumlah porsi untuk melihat estimasi bahan yang akan terpakai tanpa mengubah stok.</p>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin:14px 0;align-items:flex-end">
    <div style="flex:1;min-width:150px">
      <label>Menu</label>
      <select id="simMenu">
        @foreach($menus as $menu)
          <option value="{{ $menu->id }}">{{ $menu->name }}</option>
        @endforeach
      </select>
    </div>
    <div style="width:110px">
      <label>Jumlah Porsi</label>
      <input type="number" id="simQty" min="1" max="9999" value="2">
    </div>
    <button class="btn" id="simBtn">Hitung Kebutuhan</button>
  </div>

  <div id="simResult" class="sub">Hasil simulasi akan tampil di sini.</div>
</div>

{{-- ── Semua Menu Aktif ────────────────────────────────────────── --}}
<div class="panel" style="margin-top:16px">
  <div style="font-weight:800;margin-bottom:12px;font-size:15px">Semua Menu Aktif</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Menu</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Jumlah Bahan</th>
          @permission('menu.manage')<th>Aksi</th>@endpermission
        </tr>
      </thead>
      <tbody>
        @foreach($menus as $menu)
          <tr>
            <td><b>{{ $menu->name }}</b><br><span class="sub" style="font-size:11px">{{ $menu->description }}</span></td>
            <td><span class="tag">{{ $menu->category->name }}</span></td>
            <td><b>{{ $menu->price_formatted }}</b></td>
            <td>{{ $menu->recipeItems->count() }} bahan</td>
            @permission('menu.manage')
            <td>
              <a href="{{ route('bom.index', ['menu_id' => $menu->id]) }}" class="btn outline sm">Lihat BOM</a>
            </td>
            @endpermission
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- ──────────────── MODALS ──────────────────────────────────────── --}}

{{-- Modal: Tambah Bahan ke BOM --}}
@permission('bom.manage')
@if($selectedMenu)
<div id="addBomModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Tambah Bahan ke Resep</h2>
      <button class="btn secondary sm" onclick="closeModal('addBomModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('bom.items.store') }}">
      @csrf
      <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
      <div class="form-group" style="margin-bottom:12px">
        <label>Bahan Baku</label>
        <select name="material_id" required>
          <option value="">-- Pilih bahan --</option>
          @foreach($materials as $mat)
            <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->unit }})</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Takaran per Porsi</label>
        <input name="quantity_per_serving" type="number" min="0.001" step="any" placeholder="cth. 18" required>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('addBomModal')">Batal</button>
        <button type="submit" class="btn">Tambahkan</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Edit Takaran BOM --}}
<div id="editBomModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Ubah Takaran Bahan</h2>
      <button class="btn secondary sm" onclick="closeModal('editBomModal')">✕</button>
    </div>
    <form method="POST" id="editBomForm">
      @csrf @method('PUT')
      <div class="form-group" style="margin-bottom:12px">
        <label>Bahan</label>
        <input id="editBomMaterialName" readonly style="background:#f9f3ee;color:var(--muted)">
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Takaran per Porsi (baru)</label>
        <input id="editBomQty" name="quantity_per_serving" type="number" min="0.001" step="any" required>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('editBomModal')">Batal</button>
        <button type="submit" class="btn">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endif
@endpermission

{{-- Modal: Tambah Menu --}}
@permission('menu.manage')
<div id="addMenuModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Tambah Menu</h2>
      <button class="btn secondary sm" onclick="closeModal('addMenuModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('bom.menus.store') }}">
      @csrf
      <div class="form-group" style="margin-bottom:12px">
        <label>Nama Menu</label>
        <input name="name" required placeholder="cth. Es Kopi Susu">
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group">
          <label>Kategori</label>
          <select name="category_id" required>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Harga Jual (Rp)</label>
          <input name="price" type="number" min="0" step="any" placeholder="22000" required>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Deskripsi</label>
        <input name="description" placeholder="Deskripsi singkat menu">
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('addMenuModal')">Batal</button>
        <button type="submit" class="btn">Simpan Menu</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Edit Menu --}}
@if($selectedMenu)
<div id="editMenuModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Ubah Menu</h2>
      <button class="btn secondary sm" onclick="closeModal('editMenuModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('bom.menus.update', $selectedMenu) }}">
      @csrf @method('PUT')
      <div class="form-group" style="margin-bottom:12px">
        <label>Nama Menu</label>
        <input name="name" value="{{ $selectedMenu->name }}" required>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group">
          <label>Kategori</label>
          <select name="category_id" required>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}" {{ $selectedMenu->category_id === $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group">
          <label>Harga Jual (Rp)</label>
          <input name="price" type="number" min="0" step="any" value="{{ (float)$selectedMenu->price }}" required>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Deskripsi</label>
        <input name="description" value="{{ $selectedMenu->description }}">
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
        <input type="checkbox" name="is_active" id="menu_active" value="1" {{ $selectedMenu->is_active ? 'checked' : '' }} style="width:auto">
        <label for="menu_active" style="margin:0;cursor:pointer">Menu Aktif</label>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('editMenuModal')">Batal</button>
        <button type="submit" class="btn">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>
@endif

{{-- Modal: Tambah Kategori --}}
<div id="addCategoryModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Tambah Kategori</h2>
      <button class="btn secondary sm" onclick="closeModal('addCategoryModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('bom.categories.store') }}">
      @csrf
      <div class="form-group" style="margin-bottom:20px">
        <label>Nama Kategori</label>
        <input name="name" required placeholder="cth. Es Kopi">
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('addCategoryModal')">Batal</button>
        <button type="submit" class="btn">Simpan</button>
      </div>
    </form>
  </div>
</div>
@endpermission

@endsection

@push('scripts')
<script>
function openEditBom(id, qty, materialName) {
  document.getElementById('editBomForm').action = `/bom/items/${id}`;
  document.getElementById('editBomQty').value = qty;
  document.getElementById('editBomMaterialName').value = materialName;
  openModal('editBomModal');
}

function openEditMenuModal() {
  openModal('editMenuModal');
}

// Simulasi BOM
document.getElementById('simBtn').addEventListener('click', async () => {
  const menuId = document.getElementById('simMenu').value;
  const qty    = document.getElementById('simQty').value;
  const resEl  = document.getElementById('simResult');

  resEl.innerHTML = '<span style="color:var(--muted)">Menghitung...</span>';

  const res = await fetch('{{ route('bom.simulate') }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ menu_id: menuId, quantity: qty }),
  });

  const data = await res.json();
  if (!res.ok) { resEl.innerHTML = `<span style="color:var(--red)">${data.message}</span>`; return; }

  resEl.innerHTML = `
    <div class="note">
      <b>Kebutuhan untuk ${data.quantity} porsi ${data.menu_name}:</b><br>
      ${data.items.map(i => `
        ${i.material_name}: <b>${fmtQty(i.needed)} ${i.unit}</b>
        ${!i.sufficient ? '<span class="status empty" style="margin-left:6px">Stok kurang!</span>' : ''}
      `).join(' &nbsp;•&nbsp; ')}<br>
      <span style="color:var(--muted);font-size:11px">Simulasi ini tidak mengubah stok.</span>
    </div>
  `;
});
</script>
@endpush
