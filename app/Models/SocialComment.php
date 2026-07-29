<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'platform',
        'author_name',
        'author_id',
        'content',
        'sentiment',
        'is_flagged',
        'is_replied',
        'ai_reply',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'is_flagged' => 'boolean',
            'is_replied' => 'boolean',
            'replied_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }
}
