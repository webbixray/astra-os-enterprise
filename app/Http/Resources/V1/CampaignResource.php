<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CampaignResource extends JsonResource
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
            'objective' => $this->resource->objective,
            'budget' => [
                'amount' => (float) $this->resource->budget_amount,
                'currency' => $this->resource->budget_currency ?? 'USD',
            ],
            'target_audience' => $this->resource->target_audience ?? [],
            'platforms' => $this->resource->platforms ?? [],
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'status' => $this->resource->status,
            'metadata' => $this->resource->metadata ?? [],
            'organization_id' => $this->resource->organization_id,
            'created_by' => $this->resource->created_by,
            'launched_at' => $this->resource->launched_at,
            'paused_at' => $this->resource->paused_at,
            'archived_at' => $this->resource->archived_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'analytics' => CampaignAnalyticsResource::collection($this->whenLoaded('analytics')),
        ];
    }
}
