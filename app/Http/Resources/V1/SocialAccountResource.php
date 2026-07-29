<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SocialAccountResource extends JsonResource
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
            'platform' => $this->resource->platform,
            'account_id' => $this->resource->account_id,
            'account_name' => $this->resource->account_name,
            'is_connected' => $this->resource->is_connected,
            'token_expires_at' => $this->resource->token_expires_at,
            'metadata' => $this->resource->metadata ?? [],
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
