<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignCreative extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'campaign_id',
        'type',
        'content',
        'variant',
        'status',
        'version',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'version' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
