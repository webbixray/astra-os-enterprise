<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SocialPostResource extends JsonResource
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
            'organization_id' => $this->resource->organization_id,
            'social_account_id' => $this->resource->social_account_id,
            'content' => $this->resource->content,
            'media_url' => $this->resource->media_url,
            'platforms' => $this->resource->platforms ?? [],
            'status' => $this->resource->status,
            'scheduled_at' => $this->resource->scheduled_at,
            'published_at' => $this->resource->published_at,
            'analytics' => $this->resource->analytics ?? [],
            'social_account' => new SocialAccountResource($this->whenLoaded('socialAccount')),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
