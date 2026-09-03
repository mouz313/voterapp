<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'uc_id',
        'code',
        'area_name',
        'area_name_ur',
        'population',
    ];

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }
}
