<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'date',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'roas',
        'cpc',
        'cpm',
        'ctr',
        'source',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'conversions' => 'integer',
            'spend' => 'decimal:2',
            'revenue' => 'decimal:2',
            'roas' => 'decimal:4',
            'cpc' => 'decimal:4',
            'cpm' => 'decimal:4',
            'ctr' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
