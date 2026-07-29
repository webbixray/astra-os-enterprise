<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Models\User;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(CampaignFactory::class)]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'objective',
        'budget_amount',
        'budget_currency',
        'target_audience',
        'platforms',
        'start_date',
        'end_date',
        'status',
        'metadata',
        'organization_id',
        'created_by',
        'launched_at',
        'paused_at',
        'archived_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_amount' => 'float',
            'target_audience' => 'array',
            'platforms' => 'array',
            'metadata' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'launched_at' => 'datetime',
            'paused_at' => 'datetime',
            'archived_at' => 'datetime',
            'organization_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * Get the organization that owns the campaign.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the campaign.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the analytics records for the campaign.
     *
     * @return HasMany<CampaignAnalytics>
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(CampaignAnalytics::class, 'campaign_id');
    }
}
