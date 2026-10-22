<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'menu_id',
        'menu_name_snapshot',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relations
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function materialUsages()
    {
        return $this->hasMany(SaleMaterialUsage::class);
    }

    // Helpers
    public function getUnitPriceFormattedAttribute(): string
    {
        return 'Rp' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getLineTotalFormattedAttribute(): string
    {
        return 'Rp' . number_format($this->line_total, 0, ',', '.');
    }
}
