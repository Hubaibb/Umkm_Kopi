<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helpers
    public function getPriceFormattedAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Cek apakah semua bahan dalam BOM cukup untuk qty porsi.
     */
    public function hasEnoughStock(int $quantity = 1): bool
    {
        foreach ($this->recipeItems as $item) {
            $needed = $item->quantity_per_serving * $quantity;
            if ($item->material->current_stock < $needed) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cek apakah ada bahan yang stoknya habis.
     */
    public function hasOutOfStockMaterial(): bool
    {
        foreach ($this->recipeItems as $item) {
            if ($item->material->current_stock <= 0) {
                return true;
            }
        }
        return false;
    }
}
