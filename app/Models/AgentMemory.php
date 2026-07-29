<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMemory extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'type',
        'key',
        'content',
        'importance',
        'access_count',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'importance' => 'integer',
            'access_count' => 'integer',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
