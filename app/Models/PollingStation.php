<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollingStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uc_id',
        'block_code_id',
        'station_no',
        'name',
        'gender',
        'address',
        'male_booths',
        'female_booths',
        'total_booths',
    ];

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

    public function maleBlockCodes(): HasMany
    {
        return $this->hasMany(BlockCode::class, 'male_polling_station_id');
    }

    public function femaleBlockCodes(): HasMany
    {
        return $this->hasMany(BlockCode::class, 'female_polling_station_id');
    }

    public function getAssignedBlockCodesAttribute()
    {
        return BlockCode::where('male_polling_station_id', $this->id)
            ->orWhere('female_polling_station_id', $this->id)
            ->orderBy('code')
            ->get();
    }

    public function getAssignedBlockCodesListAttribute(): string
    {
        return $this->assigned_block_codes->pluck('code')->unique()->implode(', ');
    }

    public function getGenderLabelUrAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'مردانہ',
            'female' => 'زنانہ',
            default => 'مشترکہ',
        };
    }

    public function getGenderBadgeColorAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'primary',
            'female' => 'danger',
            default => 'success',
        };
    }

    public function getGenderIconAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'bi-gender-male',
            'female' => 'bi-gender-female',
            default => 'bi-gender-ambiguous',
        };
    }

    public function scopeMale($query)
    {
        return $query->where('gender', 'male');
    }

    public function scopeFemale($query)
    {
        return $query->where('gender', 'female');
    }

    public function scopeCombined($query)
    {
        return $query->where('gender', 'combined');
    }
}
