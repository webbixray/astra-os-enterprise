<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentConversation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_id',
        'agent_id',
        'messages',
        'context',
        'tokens_used',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'context' => 'array',
            'tokens_used' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
