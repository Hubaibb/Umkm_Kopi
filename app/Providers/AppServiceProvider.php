<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Blade;
use App\Models\Permission;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Timezone ────────────────────────────────────────────────────────────
        config(['app.timezone' => 'Asia/Jakarta']);
        date_default_timezone_set('Asia/Jakarta');

        // ── Gates berdasarkan permission slug ───────────────────────────────────
        // Daftarkan setiap permission slug sebagai Gate.
        // Superadmin selalu mendapat akses penuh.
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperadmin()) {
                return true; // bypass semua pengecekan Gate untuk Superadmin
            }
            return null; // lanjutkan ke Gate normal
        });

        // Register semua permission dari DB sebagai Gate.
        // Ini di-cache per request — tidak ada query loop per blade.
        try {
            Permission::all()->each(function (Permission $permission) {
                Gate::define($permission->slug, function (User $user) use ($permission) {
                    return $user->role?->permissions()
                        ->where('slug', $permission->slug)
                        ->exists();
                });
            });
        } catch (\Exception $e) {
            // Abaikan jika tabel belum ada (saat migrasi pertama)
        }

        // ── Blade directive @permission ─────────────────────────────────────────
        // Alternatif: @permission('pos.create') ... @endpermission
        Blade::if('permission', function (string $permissionSlug) {
            $user = auth()->user();
            if (!$user) return false;
            if ($user->isSuperadmin()) return true;
            return $user->hasPermission($permissionSlug);
        });
    }
}
