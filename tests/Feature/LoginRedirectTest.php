<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_barista_login_redirects_to_bom_index(): void
    {
        $baristaRole = Role::create([
            'name' => 'Barista',
            'slug' => 'barista',
        ]);

        $user = User::create([
            'name'      => 'Barista Test',
            'username'  => 'barista_test',
            'email'     => 'barista@test.com',
            'role_id'   => $baristaRole->id,
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login'    => 'barista_test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('bom.index'));
    }

    public function test_barista_with_pos_intended_url_still_redirects_to_bom_index(): void
    {
        $baristaRole = Role::create([
            'name' => 'Barista',
            'slug' => 'barista',
        ]);

        $user = User::create([
            'name'      => 'Barista Test',
            'username'  => 'barista_test_2',
            'email'     => 'barista2@test.com',
            'role_id'   => $baristaRole->id,
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->withSession(['url.intended' => route('pos.index')])
            ->post('/login', [
                'login'    => 'barista_test_2',
                'password' => 'password123',
            ]);

        $response->assertRedirect(route('bom.index'));
    }

    public function test_barista_accessing_root_redirects_to_bom_index(): void
    {
        $baristaRole = Role::create([
            'name' => 'Barista',
            'slug' => 'barista',
        ]);

        $user = User::create([
            'name'      => 'Barista Test',
            'username'  => 'barista_test_3',
            'email'     => 'barista3@test.com',
            'role_id'   => $baristaRole->id,
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/');
        $response->assertRedirect(route('bom.index'));
    }

    public function test_barista_accessing_show_login_redirects_to_bom_index(): void
    {
        $baristaRole = Role::create([
            'name' => 'Barista',
            'slug' => 'barista',
        ]);

        $user = User::create([
            'name'      => 'Barista Test',
            'username'  => 'barista_test_4',
            'email'     => 'barista4@test.com',
            'role_id'   => $baristaRole->id,
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get('/login');
        $response->assertRedirect(route('bom.index'));
    }

    public function test_kasir_login_redirects_to_pos_index(): void
    {
        $kasirRole = Role::create([
            'name' => 'Kasir',
            'slug' => 'kasir',
        ]);
        $permission = \App\Models\Permission::create([
            'name' => 'POS Create',
            'slug' => 'pos.create',
        ]);
        $kasirRole->permissions()->attach($permission);

        $user = User::create([
            'name'      => 'Kasir Test',
            'username'  => 'kasir_test',
            'email'     => 'kasir@test.com',
            'role_id'   => $kasirRole->id,
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'login'    => 'kasir_test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('pos.index'));
    }
}

