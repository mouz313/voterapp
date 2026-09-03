<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollingStation extends Model
{
    use HasFactory;

    protected $fillable = ['uc_id', 'block_code_id', 'name', 'address'];

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }

    public function blockCode(): BelongsTo
    {
        return $this->belongsTo(BlockCode::class, 'block_code_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }
}
