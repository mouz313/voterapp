<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProvincialAssembly extends Model
{
    use HasFactory;

    protected $fillable = [
        'national_assembly_id',
        'code',
        'name',
        'province',
        'description',
    ];

    public function nationalAssembly(): BelongsTo
    {
        return $this->belongsTo(NationalAssembly::class);
    }

    public function ucs(): HasMany
    {
        return $this->hasMany(UC::class);
    }
}
