<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter = $request->get('role')) {
            $query->whereHas('role', fn($q) => $q->where('slug', $roleFilter));
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();

        return view('accounts.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'username' => 'required|string|max:80|unique:users,username|alpha_dash',
            'email'    => 'required|email|max:190|unique:users,email',
            'role_id'  => 'required|exists:roles,id',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'is_active'=> 'boolean',
        ]);

        User::create([
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'role_id'   => $validated['role_id'],
            'password'  => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Akun \"{$validated['name']}\" berhasil dibuat.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:150',
            'username' => ['required', 'string', 'max:80', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email'    => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role_id'  => 'required|exists:roles,id',
            'is_active'=> 'boolean',
        ]);

        // Cegah Superadmin terakhir yang aktif dinonaktifkan atau ganti role-nya
        $this->guardLastSuperadmin($user, $request);

        $user->update([
            'name'      => $validated['name'],
            'username'  => $validated['username'],
            'email'     => $validated['email'],
            'role_id'   => $validated['role_id'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Akun \"{$user->name}\" berhasil diperbarui.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
        ], [
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with(
            'success',
            "Password akun \"{$user->name}\" berhasil direset."
        );
    }

    public function toggleActive(Request $request, User $user)
    {
        // Cegah menonaktifkan diri sendiri
        if ($user->id === auth()->id()) {
            return back()->withErrors(['toggle' => 'Anda tidak dapat mengubah status akun Anda sendiri.']);
        }

        // Cegah menonaktifkan Superadmin terakhir yang aktif
        if ($user->isSuperadmin() && $user->is_active) {
            $activeSuperadminCount = User::whereHas('role', fn($q) => $q->where('slug', 'superadmin'))
                ->where('is_active', true)
                ->count();

            if ($activeSuperadminCount <= 1) {
                return back()->withErrors(['toggle' => 'Tidak dapat menonaktifkan Superadmin aktif terakhir. Sistem harus memiliki minimal satu Superadmin aktif.']);
            }
        }

        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        $label = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun \"{$user->name}\" berhasil {$label}.");
    }

    /**
     * Cegah Superadmin aktif terakhir dinonaktifkan atau diganti rolenya.
     */
    private function guardLastSuperadmin(User $user, Request $request): void
    {
        if (!$user->isSuperadmin()) return;

        $superadminRole = Role::where('slug', 'superadmin')->first();
        $willChangeRole     = (int) $request->input('role_id') !== $superadminRole?->id;
        $willDeactivate     = !$request->boolean('is_active', true);

        if ($willChangeRole || $willDeactivate) {
            $activeSuperadminCount = User::whereHas('role', fn($q) => $q->where('slug', 'superadmin'))
                ->where('is_active', true)
                ->count();

            if ($activeSuperadminCount <= 1) {
                abort(422, 'Tidak dapat mengubah atau menonaktifkan Superadmin aktif terakhir.');
            }
        }
    }
}
