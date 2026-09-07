<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    use HasFactory;

    protected $table = 'partners';

    protected $fillable = [
        'name',
        'type',
        'sales_party_id',
        'phone',
        'invested_capital',
        'profit_share_pct',
        'bank_details',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'invested_capital' => 'decimal:2',
        'profit_share_pct' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function salesParty(): BelongsTo
    {
        return $this->belongsTo(SalesParty::class, 'sales_party_id');
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(PartnerPayout::class, 'partner_id');
    }

    public function getTotalPayoutsAttribute(): float
    {
        return (float) $this->payouts()->sum('amount');
    }

    public function getCapitalReturnedAttribute(): float
    {
        return (float) $this->payouts()->where('payout_type', 'capital_return')->sum('amount');
    }

    public function getCapitalRemainingAttribute(): float
    {
        return max(0, (float) ($this->invested_capital - $this->capital_returned));
    }

    public function getCapitalPaybackProgressPctAttribute(): float
    {
        if ($this->invested_capital <= 0) {
            return 100.0;
        }

        return min(100.0, round(($this->capital_returned / $this->invested_capital) * 100, 1));
    }

    public function getProfitPaidAttribute(): float
    {
        return (float) $this->payouts()->where('payout_type', 'profit_distribution')->sum('amount');
    }
}
