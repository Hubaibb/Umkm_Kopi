<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'role_id',
        'name',
        'username',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // Relations
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Permission helpers
    public function hasPermission(string $slug): bool
    {
        return $this->role?->permissions()->where('slug', $slug)->exists() ?? false;
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function isSuperadmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function defaultRoute(): string
    {
        if ($this->hasRole('barista') || strtolower($this->role?->slug ?? '') === 'barista') {
            return route('bom.index');
        }

        if ($this->hasPermission('pos.create')) {
            return route('pos.index');
        }

        if ($this->hasRole('pengelola') || strtolower($this->role?->slug ?? '') === 'pengelola') {
            return route('inventory.index');
        }

        if ($this->hasRole('pengurus-koperasi') || strtolower($this->role?->slug ?? '') === 'pengurus-koperasi') {
            return route('reports.index');
        }

        if ($this->hasPermission('bom.view')) {
            return route('bom.index');
        }

        if ($this->hasPermission('inventory.view')) {
            return route('inventory.index');
        }

        if ($this->hasPermission('reports.view')) {
            return route('reports.index');
        }

        return route('login');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Aktif' : 'Nonaktif';
    }
}
