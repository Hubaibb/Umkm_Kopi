<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — UMKM Kopi BPK RI</title>
<meta name="description" content="Login ke Sistem Operasional UMKM Kopi BPK RI">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<style>
  body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--cream); }
  .login-wrap { width: min(420px, 100%); padding: 20px; }
  .login-card {
    background: #fff;
    border-radius: 20px;
    padding: 36px 32px;
    box-shadow: 0 8px 40px rgba(60,24,5,.12);
    border: 1px solid var(--line);
  }
  .login-brand { text-align: center; margin-bottom: 28px; }
  .login-icon {
    width: 60px; height: 60px;
    background: var(--coffee); color: #fff;
    border-radius: 18px;
    display: inline-grid; place-items: center;
    margin-bottom: 14px;
  }
  .login-icon .material-symbols-outlined { font-size: 30px; }
  .login-title { font-size: 22px; font-weight: 800; color: var(--coffee); letter-spacing: -.03em; }
  .login-sub   { font-size: 12px; color: var(--muted); margin-top: 4px; }
  .field { margin-bottom: 16px; }
  .field label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; display: block; }
  .input-icon { position: relative; }
  .input-icon .material-symbols-outlined {
    position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
    color: #b09e93; font-size: 18px;
  }
  .input-icon input { padding-left: 38px; }
  .btn-login {
    width: 100%; padding: 13px; font-size: 14px;
    border-radius: 12px; margin-top: 6px;
  }
  .error-msg {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    border-radius: 10px;
    padding: 11px 13px;
    font-size: 12px;
    margin-bottom: 16px;
  }
  .footer-login { text-align: center; margin-top: 20px; font-size: 11px; color: #b09e93; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-brand">
      <div class="login-icon">
        <span class="material-symbols-outlined">local_cafe</span>
      </div>
      <div class="login-title">UMKM Kopi BPK RI</div>
      <div class="login-sub">Koperasi BPK RI · Sistem Operasional</div>
    </div>

    @if($errors->any())
      <div class="error-msg" role="alert">
        <span class="material-symbols-outlined" style="font-size:14px;vertical-align:middle;margin-right:4px">error</span>
        {{ $errors->first('login') ?? $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
      @csrf

      <div class="field">
        <label for="login">Username atau Email</label>
        <div class="input-icon">
          <span class="material-symbols-outlined">person</span>
          <input
            type="text"
            id="login"
            name="login"
            value="{{ old('login') }}"
            placeholder="Masukkan username atau email"
            autocomplete="username"
            autofocus
            required
          >
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-icon">
          <span class="material-symbols-outlined">lock</span>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Masukkan password"
            autocomplete="current-password"
            required
          >
        </div>
      </div>

      <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <input type="checkbox" id="remember" name="remember" style="width:auto;border-radius:4px">
        <label for="remember" style="margin:0;font-size:12px;font-weight:500;color:var(--muted);cursor:pointer">Ingat saya</label>
      </div>

      <button type="submit" class="btn btn-login" id="loginBtn">
        <span class="material-symbols-outlined" style="font-size:18px">login</span>
        Masuk
      </button>
    </form>

    <div class="footer-login">
      <p>Platform Operasional Terintegrasi · UMKM Kopi BPK RI</p>
    </div>
  </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;animation:spin 1s linear infinite">progress_activity</span> Memproses...';
});
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
</body>
</html>
