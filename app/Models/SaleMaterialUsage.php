<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleMaterialUsage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'sale_item_id',
        'material_id',
        'material_name_snapshot',
        'unit_snapshot',
        'quantity_used',
        'created_at',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'created_at'    => 'datetime',
    ];

    // Relations
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
