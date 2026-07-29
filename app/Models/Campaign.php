<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'objective',
        'status',
        'budget_amount',
        'budget_currency',
        'target_audience',
        'platforms',
        'start_date',
        'end_date',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'decimal:2',
            'target_audience' => 'array',
            'platforms' => 'array',
            'metadata' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(CampaignCreative::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(CampaignInsight::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(CampaignAnalytic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(AgentTask::class);
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(Workflow::class);
    }
}
