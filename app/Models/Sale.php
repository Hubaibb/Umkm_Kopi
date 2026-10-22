<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'transaction_code',
        'cashier_id',
        'transaction_status',
        'payment_method',
        'subtotal',
        'total',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'total'        => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    // Relations
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function materialUsages()
    {
        return $this->hasMany(SaleMaterialUsage::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('transaction_status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('completed_at', today());
    }

    // Helpers
    public function getTotalFormattedAttribute(): string
    {
        return 'Rp' . number_format($this->total, 0, ',', '.');
    }

    public function getItemsSummaryAttribute(): string
    {
        return $this->items->map(fn($i) => $i->menu_name_snapshot . ' × ' . $i->quantity)->implode(', ');
    }

    public function getPaymentLabelAttribute(): string
    {
        return $this->payment_method === 'cash' ? 'Tunai' : 'QRIS';
    }
}
