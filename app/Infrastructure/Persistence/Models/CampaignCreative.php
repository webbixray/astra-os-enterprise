<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Common\Contracts\AggregateRoot;
use App\Domain\Common\Traits\HasDomainEvents;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignCreative extends Model implements AggregateRoot
{
    use HasFactory, HasUuids, HasDomainEvents;

    protected $fillable = [
        'campaign_id',
        'type',
        'content',
        'variant',
        'status',
        'version',
        'approved_by',
    ];

    protected $casts = [
        'content' => 'array',
        'version' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
