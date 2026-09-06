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

    /**
     * Determine gender from Pakistani CNIC standard:
     * Odd last digit = Male (1, 3, 5, 7, 9)
     * Even last digit = Female (0, 2, 4, 6, 8)
     */
    public function getGenderAttribute(): string
    {
        $clean = preg_replace('/\D/', '', (string) $this->cnic);
        if (strlen($clean) < 1) {
            return 'male';
        }
        $lastDigit = (int) substr($clean, -1);
        return ($lastDigit % 2 === 0) ? 'female' : 'male';
    }

    public function getGenderLabelUrAttribute(): string
    {
        return $this->gender === 'female' ? 'عورت' : 'مرد';
    }

    public function getGenderBadgeColorAttribute(): string
    {
        return $this->gender === 'female' ? 'danger' : 'primary';
    }

    /**
     * Resolve the appropriate polling station ID based on voter gender & block code.
     */
    public function resolvePollingStationId(): ?int
    {
        $block = $this->blockCode;
        if (!$block) {
            return $this->polling_station_id;
        }

        if ($this->gender === 'female') {
            return $block->female_polling_station_id 
                ?: $block->male_polling_station_id 
                ?: $this->polling_station_id;
        }

        return $block->male_polling_station_id 
            ?: $block->female_polling_station_id 
            ?: $this->polling_station_id;
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
