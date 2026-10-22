<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    protected $fillable = [
        'menu_id',
        'material_id',
        'quantity_per_serving',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_per_serving' => 'decimal:3',
    ];

    // Relations
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helpers
    public function getQtyFormattedAttribute(): string
    {
        $val = (float) $this->quantity_per_serving;
        return rtrim(rtrim(number_format($val, 3, ',', '.'), '0'), ',');
    }

    /**
     * Estimasi stok material setelah 1 porsi.
     */
    public function getStockAfterOneServingAttribute(): float
    {
        return (float) $this->material->current_stock - (float) $this->quantity_per_serving;
    }
}
