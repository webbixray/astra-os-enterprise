<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'impressions' => $this->resource['impressions'] ?? 0,
            'clicks' => $this->resource['clicks'] ?? 0,
            'conversions' => $this->resource['conversions'] ?? 0,
            'spend' => $this->resource['spend'] ?? 0.0,
            'revenue' => $this->resource['revenue'] ?? 0.0,
            'ctr' => $this->resource['ctr'] ?? 0.0,
            'cvr' => $this->resource['cvr'] ?? 0.0,
            'roas' => $this->resource['roas'] ?? 0.0,
            'cpc' => $this->resource['cpc'] ?? 0.0,
            'time_series' => $this->resource['time_series'] ?? [],
            'platforms' => $this->resource['platforms'] ?? [],
        ];
    }
}
