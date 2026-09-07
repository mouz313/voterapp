<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesParty extends Model
{
    use HasFactory;

    protected $table = 'sales_parties';

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'default_price',
        'commission_rate_pct',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'commission_rate_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(CandidateSale::class, 'sales_party_id');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class, 'sales_party_id');
    }

    public function getTotalSalesCountAttribute(): int
    {
        return $this->sales()->count();
    }

    public function getTotalRevenueAttribute(): float
    {
        return (float) $this->sales()->sum('amount_paid');
    }

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->sales()->sum('sale_amount');
    }

    public function getPendingAmountAttribute(): float
    {
        return (float) ($this->total_invoiced - $this->total_revenue);
    }
}
