<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'current_stock',
        'minimum_stock',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'is_active'     => 'boolean',
    ];

    // Relations
    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function getStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'Habis';
        }
        if ($this->current_stock <= $this->minimum_stock) {
            return 'Menipis';
        }
        return 'Aman';
    }

    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'Habis'   => 'empty',
            'Menipis' => 'low',
            default   => 'safe',
        };
    }

    public function getStockFormattedAttribute(): string
    {
        $val = (float) $this->current_stock;
        return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
    }

    public function getMinStockFormattedAttribute(): string
    {
        $val = (float) $this->minimum_stock;
        return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
    }
}
