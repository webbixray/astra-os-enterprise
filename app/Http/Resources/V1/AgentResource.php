<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AgentResource extends JsonResource
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
            'name' => $this->resource->name,
            'role' => $this->resource->role,
            'description' => $this->resource->description,
            'model' => $this->resource->model,
            'capabilities' => $this->resource->capabilities ?? [],
            'configuration' => $this->resource->configuration ?? [],
            'status' => $this->resource->status,
            'organization_id' => $this->resource->organization_id,
            'tasks' => AgentTaskResource::collection($this->whenLoaded('tasks')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
