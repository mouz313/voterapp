<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voter extends Model
{
    use HasFactory;

    protected $fillable = [
        'uc_id',
        'block_code_id',
        'polling_station_id',
        'name',
        'father_name',
        'age',
        'cnic',
        'silsala_no',
        'gharana_no',
        'address',
    ];

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }

    public function blockCode(): BelongsTo
    {
        return $this->belongsTo(BlockCode::class, 'block_code_id');
    }

    public function pollingStation(): BelongsTo
    {
        return $this->belongsTo(PollingStation::class, 'polling_station_id');
    }

    /**
     * Strip dashes/spaces so "12345-1234567-8" and "1234512345678" match.
     */
    public static function normalizeCnic(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    /**
     * Format a raw CNIC as 12345-1234567-8 for display.
     */
    public static function formatCnic(string $value): string
    {
        $n = self::normalizeCnic($value);
        if (strlen($n) === 13) {
            return substr($n, 0, 5) . '-' . substr($n, 5, 7) . '-' . substr($n, 12, 1);
        }
        return $value;
    }

    public function getFormattedCnicAttribute(): string
    {
        return self::formatCnic($this->cnic);
    }

    public function scopeByCnic(Builder $query, string $cnic): Builder
    {
        return $query->where('cnic', self::normalizeCnic($cnic));
    }

    public function scopeByGharana(Builder $query, string $gharana): Builder
    {
        return $query->where('gharana_no', $gharana);
    }
}
