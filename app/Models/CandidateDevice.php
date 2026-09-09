<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_uid',
        'api_token',
        'fcm_token',
        'device_name',
        'platform',
        'app_version',
        'ip_address',
        'last_active_at',
        'is_revoked',
    ];

    protected $hidden = [
        'api_token',
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'is_revoked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
