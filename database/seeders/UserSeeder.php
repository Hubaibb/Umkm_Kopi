<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superadminRole = Role::where('slug', 'superadmin')->firstOrFail();

        // Superadmin awal
        User::updateOrCreate(
            ['username' => 'superadmin'],
            [
                'role_id'   => $superadminRole->id,
                'name'      => 'Super Admin',
                'username'  => 'superadmin',
                'email'     => 'superadmin@umkmkopi.id',
                'password'  => Hash::make('SuperAdmin@2024!'),
                'is_active' => true,
            ]
        );

        // Akun demo tambahan
        $demoAccounts = [
            [
                'slug'     => 'admin',
                'name'     => 'Admin Kopi',
                'username' => 'admin',
                'email'    => 'admin@umkmkopi.id',
                'password' => 'Admin@2024!',
            ],
            [
                'slug'     => 'kasir',
                'name'     => 'Kasir Demo',
                'username' => 'kasir',
                'email'    => 'kasir@umkmkopi.id',
                'password' => 'Kasir@2024!',
            ],
            [
                'slug'     => 'pengelola',
                'name'     => 'Pengelola Demo',
                'username' => 'pengelola',
                'email'    => 'pengelola@umkmkopi.id',
                'password' => 'Pengelola@2024!',
            ],
        ];

        foreach ($demoAccounts as $demo) {
            $role = Role::where('slug', $demo['slug'])->first();
            if ($role) {
                User::updateOrCreate(
                    ['username' => $demo['username']],
                    [
                        'role_id'   => $role->id,
                        'name'      => $demo['name'],
                        'username'  => $demo['username'],
                        'email'     => $demo['email'],
                        'password'  => Hash::make($demo['password']),
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Users berhasil di-seed.');
        $this->command->line('  Superadmin: username=superadmin | password=SuperAdmin@2024!');
        $this->command->line('  Admin:      username=admin      | password=Admin@2024!');
        $this->command->line('  Kasir:      username=kasir      | password=Kasir@2024!');
    }
}
