<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AgentTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'agent_id' => $this->resource->agent_id,
            'type' => $this->resource->type,
            'status' => $this->resource->status,
            'input' => $this->resource->input ?? [],
            'output' => $this->resource->output,
            'error' => $this->resource->error,
            'parent_task_id' => $this->resource->parent_task_id,
            'completed_at' => $this->resource->completed_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
