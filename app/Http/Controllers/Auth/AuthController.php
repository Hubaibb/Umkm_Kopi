<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->defaultRoute());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');

        // Tentukan login menggunakan email atau username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $user = \App\Models\User::where($field, $login)->first();

        // Validasi user, status aktif, dan password bcrypt
        if (
            !$user ||
            !$user->is_active ||
            !Hash::check($password, $user->password)
        ) {
            throw ValidationException::withMessages([
                'login' => ['Kredensial tidak valid atau akun tidak aktif.'],
            ]);
        }

        // Login manual setelah password berhasil diverifikasi
        Auth::login($user, $request->boolean('remember'));

        // Perbarui waktu login
        $user->update([
            'last_login_at' => now(),
        ]);

        // Regenerasi session untuk keamanan
        $request->session()->regenerate();

        // Jika akun adalah barista, arahkan langsung ke Resep/BOM dan jangan ke menu POS
        if ($user->hasRole('barista') || strtolower($user->role?->slug ?? '') === 'barista') {
            $intended = $request->session()->pull('url.intended');
            if ($intended && !str_contains($intended, '/pos')) {
                return redirect()->to($intended);
            }
            return redirect()->route('bom.index');
        }

        return redirect()->intended($user->defaultRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}