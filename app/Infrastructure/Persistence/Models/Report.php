<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'config',
        'schedule',
        'last_run_at',
        'format',
        'recipients',
    ];

    protected $casts = [
        'config' => 'array',
        'recipients' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeScheduled($query)
    {
        return $query->whereNotNull('schedule');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
