<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    public function activeMenus()
    {
        return $this->hasMany(Menu::class)->where('is_active', true);
    }
}
