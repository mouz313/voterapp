<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
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
    'party_logo',
    'candidate_image',
    'candidate_symbol_image',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCandidate(): bool
    {
        return $this->role === 'candidate';
    }
}
