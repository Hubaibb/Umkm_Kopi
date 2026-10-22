<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Permissions ───────────────────────────────────────────────────────
        $permissions = [
            // POS
            ['name' => 'POS - Buat Transaksi',        'slug' => 'pos.create',         'description' => 'Membuat transaksi baru melalui POS'],
            // Inventory
            ['name' => 'Stok - Lihat',                'slug' => 'inventory.view',      'description' => 'Melihat daftar bahan baku dan stok'],
            ['name' => 'Stok - Kelola',               'slug' => 'inventory.manage',    'description' => 'Tambah/ubah/nonaktifkan bahan baku'],
            ['name' => 'Stok - Sesuaikan',            'slug' => 'inventory.adjust',    'description' => 'Melakukan penyesuaian stok manual'],
            // BOM / Menu
            ['name' => 'Menu - Lihat',                'slug' => 'menu.view',           'description' => 'Melihat daftar menu'],
            ['name' => 'Menu - Kelola',               'slug' => 'menu.manage',         'description' => 'Tambah/ubah/nonaktifkan menu dan kategori'],
            ['name' => 'BOM - Lihat',                 'slug' => 'bom.view',            'description' => 'Melihat resep/BOM'],
            ['name' => 'BOM - Kelola',                'slug' => 'bom.manage',          'description' => 'Tambah/ubah/hapus komposisi BOM'],
            // Analitik & Laporan
            ['name' => 'Laporan - Lihat',             'slug' => 'reports.view',        'description' => 'Melihat analitik penjualan'],
            ['name' => 'Laporan - Ekspor',            'slug' => 'reports.export',      'description' => 'Mengunduh ekspor CSV'],
            // Transaksi
            ['name' => 'Transaksi - Lihat',           'slug' => 'transactions.view',   'description' => 'Melihat riwayat transaksi'],
            // Akun
            ['name' => 'Akun - Lihat',                'slug' => 'accounts.view',       'description' => 'Melihat daftar akun'],
            ['name' => 'Akun - Buat',                 'slug' => 'accounts.create',     'description' => 'Membuat akun baru'],
            ['name' => 'Akun - Ubah',                 'slug' => 'accounts.update',     'description' => 'Mengubah data akun'],
            ['name' => 'Akun - Hapus/Nonaktifkan',    'slug' => 'accounts.delete',     'description' => 'Menonaktifkan akun'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['slug' => $perm['slug']], $perm);
        }

        // ─── Roles ─────────────────────────────────────────────────────────────
        $roles = [
            [
                'name'        => 'Superadmin',
                'slug'        => 'superadmin',
                'description' => 'Akses penuh ke semua fitur sistem',
                'permissions' => Permission::pluck('slug')->toArray(), // semua permission
            ],
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'description' => 'Akses operasional dan pengelolaan data, tanpa kendali Superadmin',
                'permissions' => [
                    'pos.create', 'inventory.view', 'inventory.manage', 'inventory.adjust',
                    'menu.view', 'menu.manage', 'bom.view', 'bom.manage',
                    'reports.view', 'reports.export', 'transactions.view',
                    'accounts.view', 'accounts.create', 'accounts.update',
                ],
            ],
            [
                'name'        => 'Kasir',
                'slug'        => 'kasir',
                'description' => 'Membuat transaksi POS dan melihat riwayat',
                'permissions' => [
                    'pos.create', 'menu.view', 'transactions.view',
                ],
            ],
            [
                'name'        => 'Barista',
                'slug'        => 'barista',
                'description' => 'Melihat resep BOM dan stok',
                'permissions' => [
                    'menu.view', 'bom.view', 'inventory.view',
                ],
            ],
            [
                'name'        => 'Pengelola',
                'slug'        => 'pengelola',
                'description' => 'Mengelola bahan baku, stok, menu/BOM, dan laporan',
                'permissions' => [
                    'inventory.view', 'inventory.manage', 'inventory.adjust',
                    'menu.view', 'menu.manage', 'bom.view', 'bom.manage',
                    'reports.view', 'reports.export', 'transactions.view',
                ],
            ],
            [
                'name'        => 'Pengurus Koperasi',
                'slug'        => 'pengurus-koperasi',
                'description' => 'Melihat analitik/laporan dan riwayat (baca saja)',
                'permissions' => [
                    'reports.view', 'reports.export', 'transactions.view',
                    'inventory.view', 'menu.view', 'bom.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $perms = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::updateOrCreate(['slug' => $roleData['slug']], $roleData);

            $permIds = Permission::whereIn('slug', $perms)->pluck('id')->toArray();
            $role->permissions()->sync($permIds);
        }

        $this->command->info('Roles dan permissions berhasil di-seed.');
    }
}
