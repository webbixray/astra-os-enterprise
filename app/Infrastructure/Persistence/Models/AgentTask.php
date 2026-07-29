<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\AgentTaskFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentTask extends Model
{
    /** @use HasFactory<AgentTaskFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'agent_id',
        'type',
        'status',
        'input',
        'output',
        'error',
        'parent_task_id',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'agent_id' => 'integer',
            'parent_task_id' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the agent that owns the task.
     *
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    /**
     * Get the parent task.
     *
     * @return BelongsTo<AgentTask, $this>
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(AgentTask::class, 'parent_task_id');
    }
}
