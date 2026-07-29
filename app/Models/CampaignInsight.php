<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'date',
        'metric',
        'value',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
