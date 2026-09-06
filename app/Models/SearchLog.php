<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uc_id',
        'device_uid',
        'query_type',
        'results_count',
        'cnic_count',
        'name_count',
        'gharana_count',
        'silsala_count',
        'searched_at',
    ];

    protected function casts(): array
    {
        return [
            'searched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uc(): BelongsTo
    {
        return $this->belongsTo(UC::class, 'uc_id');
    }
}
