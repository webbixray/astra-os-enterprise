<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMemory extends Model
{
    protected $fillable = [
        'agent_id',
        'type',
        'key',
        'content',
        'importance',
        'access_count',
        'last_accessed_at',
    ];

    protected $casts = [
        'content' => 'array',
        'importance' => 'integer',
        'access_count' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeImportant($query, int $minImportance = 5)
    {
        return $query->where('importance', '>=', $minImportance);
    }
}
