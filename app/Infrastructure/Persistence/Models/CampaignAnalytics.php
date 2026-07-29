<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\CampaignAnalyticsFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(CampaignAnalyticsFactory::class)]
class CampaignAnalytics extends Model
{
    /** @use HasFactory<CampaignAnalyticsFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'campaign_analytics';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'campaign_id',
        'impressions',
        'clicks',
        'conversions',
        'spend',
        'revenue',
        'date',
        'platform',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'impressions' => 'integer',
            'clicks' => 'integer',
            'conversions' => 'integer',
            'spend' => 'float',
            'revenue' => 'float',
            'date' => 'date',
            'campaign_id' => 'integer',
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the campaign that owns the analytics.
     *
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Get the click-through rate.
     */
    public function getCTRAttribute(): float
    {
        return $this->impressions > 0
            ? round(($this->clicks / $this->impressions) * 100, 2)
            : 0.0;
    }

    /**
     * Get the conversion rate.
     */
    public function getCVRAttribute(): float
    {
        return $this->clicks > 0
            ? round(($this->conversions / $this->clicks) * 100, 2)
            : 0.0;
    }

    /**
     * Get the return on ad spend.
     */
    public function getROASAttribute(): float
    {
        return $this->spend > 0
            ? round($this->revenue / $this->spend, 2)
            : 0.0;
    }
}
