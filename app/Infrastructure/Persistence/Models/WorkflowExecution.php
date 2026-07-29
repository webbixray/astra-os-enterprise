<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\WorkflowExecutionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(WorkflowExecutionFactory::class)]
class WorkflowExecution extends Model
{
    /** @use HasFactory<WorkflowExecutionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workflow_executions';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workflow_id',
        'status',
        'input_variables',
        'results',
        'error',
        'execution_time_ms',
        'started_at',
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
            'input_variables' => 'array',
            'results' => 'array',
            'execution_time_ms' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'workflow_id' => 'integer',
        ];
    }

    /**
     * Get the workflow that owns the execution.
     *
     * @return BelongsTo<Workflow, $this>
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
