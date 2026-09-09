<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GharanaSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'block_code',
        'gharana_no',
        'sentiment',
        'influencer_name',
        'influencer_phone',
        'voter_count',
        'notes',
        'is_vip_visit_requested',
        'visited_by_worker_id',
        'visited_at',
        'parchi_issued_at',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'gharana_no' => 'integer',
        'voter_count' => 'integer',
        'is_vip_visit_requested' => 'boolean',
        'visited_at' => 'datetime',
        'parchi_issued_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(CampaignWorker::class, 'visited_by_worker_id');
    }

    public function scopePakka($query)
    {
        return $query->where('sentiment', 'pakka');
    }

    public function scopeKacha($query)
    {
        return $query->where('sentiment', 'kacha');
    }

    public function scopeMukhalif($query)
    {
        return $query->where('sentiment', 'mukhalif');
    }

    public function scopeVipVisit($query)
    {
        return $query->where('is_vip_visit_requested', true);
    }
}
