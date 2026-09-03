<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NationalAssembly extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'province',
        'description',
    ];

    public function provincialAssemblies(): HasMany
    {
        return $this->hasMany(ProvincialAssembly::class);
    }

    public function ucs(): HasMany
    {
        return $this->hasMany(UC::class);
    }
}
