<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CampaignAnalyticsResource extends JsonResource
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
            'campaign_id' => $this->resource->campaign_id,
            'impressions' => $this->resource->impressions,
            'clicks' => $this->resource->clicks,
            'conversions' => $this->resource->conversions,
            'spend' => (float) $this->resource->spend,
            'revenue' => (float) $this->resource->revenue,
            'date' => $this->resource->date,
            'platform' => $this->resource->platform,
            'ctr' => $this->resource->ctr,
            'cvr' => $this->resource->cvr,
            'roas' => $this->resource->roas,
        ];
    }
}
