<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateSale extends Model
{
    use HasFactory;

    protected $table = 'candidate_sales';

    protected $fillable = [
        'candidate_id',
        'sales_party_id',
        'sale_amount',
        'amount_paid',
        'payment_status',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(SalesParty::class, 'sales_party_id');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) ($this->sale_amount - $this->amount_paid));
    }
}
