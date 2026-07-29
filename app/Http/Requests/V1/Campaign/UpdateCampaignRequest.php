<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Campaign;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'objective' => ['sometimes', 'string', 'in:brand_awareness,lead_generation,conversions,engagement,traffic,sales'],
            'budget_amount' => ['sometimes', 'numeric', 'min:0'],
            'budget_currency' => ['sometimes', 'string', 'size:3'],
            'target_audience' => ['nullable', 'array'],
            'platforms' => ['sometimes', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:google_ads,meta_ads,linkedin_ads,twitter_ads,tiktok_ads,programmatic'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
