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
        'male_polling_station_id',
        'female_polling_station_id',
    ];

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }

    public function voters(): HasMany
    {
        return $this->hasMany(Voter::class);
    }

    public function malePollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class, 'male_polling_station_id');
    }

    public function femalePollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class, 'female_polling_station_id');
    }

    /**
     * Resolve designated polling station for a voter based on CNIC gender parity.
     */
    public function getPollingStationForVoter(string|Voter $voter): ?PollingStation
    {
        $cnic = is_string($voter) ? $voter : $voter->cnic;
        $clean = preg_replace('/\D/', '', (string) $cnic);
        if (empty($clean)) {
            return $this->malePollingStation ?? $this->femalePollingStation;
        }

        $lastDigit = (int) substr($clean, -1);
        $isFemale = ($lastDigit % 2 === 0);

        if ($isFemale) {
            return $this->femalePollingStation ?? $this->malePollingStation;
        }

        return $this->malePollingStation ?? $this->femalePollingStation;
    }

    /**
     * Check if block code has both male and female polling stations assigned.
     */
    public function getIsFullyMappedAttribute(): bool
    {
        return !empty($this->male_polling_station_id) && !empty($this->female_polling_station_id);
    }
}
