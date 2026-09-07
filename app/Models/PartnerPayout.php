<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerPayout extends Model
{
    use HasFactory;

    protected $table = 'partner_payouts';

    protected $fillable = [
        'partner_id',
        'amount',
        'payout_type',
        'payout_date',
        'payment_method',
        'reference_no',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payout_date' => 'date',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}
