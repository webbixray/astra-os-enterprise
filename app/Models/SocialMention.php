<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMention extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'organization_id',
        'platform',
        'mention_url',
        'author_name',
        'content',
        'sentiment',
        'reach',
        'ai_suggested_response',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reach' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
