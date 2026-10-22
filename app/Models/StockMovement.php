<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'material_id',
        'user_id',
        'sale_id',
        'movement_type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'source',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:3',
        'stock_before'    => 'decimal:3',
        'stock_after'     => 'decimal:3',
        'created_at'      => 'datetime',
    ];

    // Relations
    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    // Helpers
    public function getChangeFormattedAttribute(): string
    {
        $val = (float) $this->quantity_change;
        $prefix = $val >= 0 ? '+' : '';
        return $prefix . rtrim(rtrim(number_format(abs($val), 3, ',', '.'), '0'), ',');
    }

    public function getMovementTypeLabelAttribute(): string
    {
        return match ($this->movement_type) {
            'purchase_in'         => 'Stok Masuk',
            'manual_in'           => 'Masuk Manual',
            'manual_out'          => 'Keluar Manual',
            'physical_adjustment' => 'Penyesuaian Fisik',
            'sale_usage'          => 'Pemakaian Transaksi',
            'correction'          => 'Koreksi',
            default               => ucfirst($this->movement_type),
        };
    }
}
