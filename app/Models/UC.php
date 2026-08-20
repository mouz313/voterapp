<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UC extends Model
{
    use HasFactory;

    protected $table = 'ucs';

    protected $fillable = ['tehsil_id', 'name'];

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }

    public function blockCodes(): HasMany
    {
        return $this->hasMany(BlockCode::class, 'uc_id');
    }

    public function pollingStations(): HasMany
    {
        return $this->hasMany(PollingStation::class, 'uc_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class, 'uc_id');
    }
}
