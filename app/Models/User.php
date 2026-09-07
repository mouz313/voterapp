<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'candidate_code',
    'password',
    'role',
    'uc_id',
    'phone',
    'max_devices',
    'status',
    'expires_at',
    'party_name',
    'is_independent',
    'candidate_symbol',
    'party_slogan',
    'party_logo',
    'candidate_image',
    'candidate_symbol_image',
    'leader_image',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if ($user->role === 'candidate' && empty($user->candidate_code)) {
                $user->candidate_code = static::generateUniqueCandidateCode($user->party_name);
            }
        });
    }

    public static function generateUniqueCandidateCode(?string $partyName = null): string
    {
        $prefix = 'CAN';
        if (!empty($partyName)) {
            $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $partyName));
            $prefix = substr($clean, 0, 3) ?: 'CAN';
        }
        do {
            $code = $prefix . '-' . mt_rand(1000, 9999);
        } while (static::where('candidate_code', $code)->exists());

        return $code;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'expires_at' => 'datetime',
            'max_devices' => 'integer',
            'is_independent' => 'boolean',
        ];
    }

    public function getPartyLogoUrlAttribute(): ?string
    {
        return $this->party_logo ? asset($this->party_logo) : null;
    }

    public function getCandidateImageUrlAttribute(): ?string
    {
        return $this->candidate_image ? asset($this->candidate_image) : null;
    }

    public function getCandidateSymbolImageUrlAttribute(): ?string
    {
        return $this->candidate_symbol_image ? asset($this->candidate_symbol_image) : null;
    }

    public function getLeaderImageUrlAttribute(): ?string
    {
        return $this->leader_image ? asset($this->leader_image) : null;
    }

    public function campaignWorkers(): HasMany
    {
        return $this->hasMany(CampaignWorker::class, 'candidate_id');
    }

    public function gharanaSurveys(): HasMany
    {
        return $this->hasMany(GharanaSurvey::class, 'candidate_id');
    }

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(CandidateDevice::class);
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(CandidateDevice::class)->where('is_revoked', false);
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(CandidateSale::class, 'candidate_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCandidate(): bool
    {
        return $this->role === 'candidate';
    }
}
