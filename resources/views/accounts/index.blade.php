@extends('layouts.app')
@section('title', 'Manajemen Akun')

@section('content')
<div class="eyebrow">06 / Account Management</div>
<h1 class="title">Manajemen Akun</h1>
<p class="sub">Kelola akun pengguna, role, dan hak akses sistem.</p>

<div class="panel" style="margin-top:18px">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:15px">
    <div style="font-weight:800;font-size:15px">Daftar Akun</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <form method="GET" action="{{ route('accounts.index') }}" style="display:flex;gap:8px;flex-wrap:wrap">
        <input name="search" value="{{ request('search') }}" placeholder="Cari nama/username/email..." style="width:210px">
        <select name="role" onchange="this.form.submit()" style="width:auto">
          <option value="">Semua role</option>
          @foreach($roles as $role)
            <option value="{{ $role->slug }}" {{ request('role') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
          @endforeach
        </select>
        <button type="submit" class="btn outline sm">Filter</button>
      </form>
      @permission('accounts.create')
        <button class="btn sm" onclick="openModal('addAccountModal')">+ Akun Baru</button>
      @endpermission
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Nama</th>
          <th>Username</th>
          <th>Email</th>
          <th>Role</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="user-avatar" style="width:30px;height:30px;font-size:12px">
                  {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <b>{{ $user->name }}</b>
                @if($user->id === auth()->id())
                  <span class="tag" style="font-size:9px">Saya</span>
                @endif
              </div>
            </td>
            <td><code style="background:#f2e6dc;padding:2px 6px;border-radius:5px;font-size:12px">{{ $user->username }}</code></td>
            <td style="color:var(--muted)">{{ $user->email }}</td>
            <td><span class="tag">{{ $user->role->name }}</span></td>
            <td>
              <span class="status {{ $user->is_active ? 'active-badge' : 'inactive-badge' }}">
                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td style="color:var(--muted);font-size:12px">{{ $user->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</td>
            <td>
              <div style="display:flex;gap:5px;flex-wrap:wrap">
                @permission('accounts.update')
                  <button class="btn secondary sm" onclick="openEditAccount(
                    {{ $user->id }},
                    '{{ addslashes($user->name) }}',
                    '{{ addslashes($user->username) }}',
                    '{{ addslashes($user->email) }}',
                    {{ $user->role_id }},
                    {{ $user->is_active ? 'true' : 'false' }}
                  )">Ubah</button>
                  <button class="btn outline sm" onclick="openResetPw({{ $user->id }}, '{{ addslashes($user->name) }}')">
                    Reset PW
                  </button>
                @endpermission
                @permission('accounts.delete')
                  @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('accounts.toggle-active', $user) }}"
                      onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $user->name }}?')">
                      @csrf
                      <button type="submit" class="btn {{ $user->is_active ? 'danger' : 'success-btn' }} sm">
                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                      </button>
                    </form>
                  @endif
                @endpermission
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7">
              <div class="empty-state">
                <span class="material-symbols-outlined">manage_accounts</span>
                Tidak ada akun ditemukan.
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $users->links('vendor.pagination.custom') }}
</div>

{{-- ─────────────────── MODALS ─────────────────────────────────── --}}

{{-- Modal: Tambah Akun --}}
@permission('accounts.create')
<div id="addAccountModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Tambah Akun Baru</h2>
      <button class="btn secondary sm" onclick="closeModal('addAccountModal')">✕</button>
    </div>
    <form method="POST" action="{{ route('accounts.store') }}">
      @csrf
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input name="name" required placeholder="cth. Budi Santoso">
        </div>
        <div class="form-group">
          <label>Username</label>
          <input name="username" required placeholder="cth. budi.santoso">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Email</label>
        <input name="email" type="email" required placeholder="budi@umkmkopi.id">
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Role</label>
        <select name="role_id" required>
          @foreach($roles as $role)
            <option value="{{ $role->id }}">{{ $role->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group">
          <label>Password</label>
          <input name="password" type="password" required placeholder="Min. 8 karakter">
        </div>
        <div class="form-group">
          <label>Konfirmasi Password</label>
          <input name="password_confirmation" type="password" required>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
        <input type="checkbox" name="is_active" id="new_acc_active" value="1" checked style="width:auto">
        <label for="new_acc_active" style="margin:0;cursor:pointer">Akun Aktif</label>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('addAccountModal')">Batal</button>
        <button type="submit" class="btn">Buat Akun</button>
      </div>
    </form>
  </div>
</div>
@endpermission

{{-- Modal: Edit Akun --}}
@permission('accounts.update')
<div id="editAccountModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Ubah Akun</h2>
      <button class="btn secondary sm" onclick="closeModal('editAccountModal')">✕</button>
    </div>
    <form method="POST" id="editAccountForm">
      @csrf @method('PUT')
      <div class="form-row" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div class="form-group">
          <label>Nama Lengkap</label>
          <input id="edit_acc_name" name="name" required>
        </div>
        <div class="form-group">
          <label>Username</label>
          <input id="edit_acc_username" name="username" required>
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Email</label>
        <input id="edit_acc_email" name="email" type="email" required>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label>Role</label>
        <select id="edit_acc_role" name="role_id" required>
          @foreach($roles as $role)
            <option value="{{ $role->id }}">{{ $role->name }}</option>
          @endforeach
        </select>
      </div>
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px">
        <input type="checkbox" name="is_active" id="edit_acc_active" value="1" style="width:auto">
        <label for="edit_acc_active" style="margin:0;cursor:pointer">Akun Aktif</label>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('editAccountModal')">Batal</button>
        <button type="submit" class="btn">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: Reset Password --}}
<div id="resetPwModal" class="modal-bg">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
      <h2 class="modal-title">Reset Password</h2>
      <button class="btn secondary sm" onclick="closeModal('resetPwModal')">✕</button>
    </div>
    <p class="sub" id="resetPwDesc" style="margin-bottom:16px">Atur password baru untuk akun ini.</p>
    <form method="POST" id="resetPwForm">
      @csrf
      <div class="form-group" style="margin-bottom:12px">
        <label>Password Baru</label>
        <input name="password" type="password" required placeholder="Min. 8 karakter">
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Konfirmasi Password Baru</label>
        <input name="password_confirmation" type="password" required>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn secondary" onclick="closeModal('resetPwModal')">Batal</button>
        <button type="submit" class="btn">Reset Password</button>
      </div>
    </form>
  </div>
</div>
@endpermission

@endsection

@push('scripts')
<script>
function openEditAccount(id, name, username, email, roleId, isActive) {
  document.getElementById('editAccountForm').action = `/accounts/${id}`;
  document.getElementById('edit_acc_name').value     = name;
  document.getElementById('edit_acc_username').value = username;
  document.getElementById('edit_acc_email').value    = email;
  document.getElementById('edit_acc_role').value     = roleId;
  document.getElementById('edit_acc_active').checked = isActive;
  openModal('editAccountModal');
}

function openResetPw(id, name) {
  document.getElementById('resetPwForm').action = `/accounts/${id}/reset-password`;
  document.getElementById('resetPwDesc').textContent = `Atur password baru untuk akun: ${name}`;
  openModal('resetPwModal');
}
</script>
@endpush
