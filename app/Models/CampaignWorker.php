<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CampaignWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'name',
        'phone',
        'pin',
        'assigned_block_code',
        'device_uid',
        'api_token',
        'last_sync_at',
        'is_active',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(GharanaSurvey::class, 'visited_by_worker_id');
    }

    public function generateApiToken(): string
    {
        $token = Str::random(60);
        $this->update(['api_token' => hash('sha256', $token)]);
        return $token;
    }
}
