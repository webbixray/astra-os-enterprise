<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkflowResource extends JsonResource
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
            'description' => $this->resource->description,
            'nodes' => $this->resource->nodes ?? [],
            'edges' => $this->resource->edges ?? [],
            'triggers' => $this->resource->triggers ?? [],
            'variables' => $this->resource->variables ?? [],
            'status' => $this->resource->status,
            'organization_id' => $this->resource->organization_id,
            'version' => $this->resource->version,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
