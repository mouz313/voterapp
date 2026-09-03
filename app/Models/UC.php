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

    protected $fillable = [
        'tehsil_id',
        'uc_no',
        'national_assembly_id',
        'provincial_assembly_id',
        'name',
        'name_ur',
    ];

    public function tehsil(): BelongsTo
    {
        return $this->belongsTo(Tehsil::class);
    }

    public function nationalAssembly(): BelongsTo
    {
        return $this->belongsTo(NationalAssembly::class, 'national_assembly_id');
    }

    public function provincialAssembly(): BelongsTo
    {
        return $this->belongsTo(ProvincialAssembly::class, 'provincial_assembly_id');
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

    public function candidates(): HasMany
    {
        return $this->hasMany(User::class, 'uc_id')->where('role', 'candidate');
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class, 'uc_id');
    }

    /**
     * Full formatted title with Tehsil and UC number.
     * e.g., "Shalimar - UC 1 (Shadi Pura)"
     */
    public function getFullTitleAttribute(): string
    {
        $tehsilName = $this->tehsil ? $this->tehsil->name : '';
        $noPrefix = $this->uc_no ? "UC {$this->uc_no}" : "UC";
        return trim("{$tehsilName} - {$noPrefix}: {$this->name}");
    }
}
