<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Campaign;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['required', 'string', 'in:brand_awareness,lead_generation,conversions,engagement,traffic,sales'],
            'budget_amount' => ['required', 'numeric', 'min:0'],
            'budget_currency' => ['sometimes', 'string', 'size:3'],
            'target_audience' => ['nullable', 'array'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:google_ads,meta_ads,linkedin_ads,twitter_ads,tiktok_ads,programmatic'],
            'start_date' => ['required', 'date', 'after:now'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'objective.in' => 'Invalid objective. Valid options: brand_awareness, lead_generation, conversions, engagement, traffic, sales.',
            'platforms.*.in' => 'Invalid platform. Valid platforms: google_ads, meta_ads, linkedin_ads, twitter_ads, tiktok_ads, programmatic.',
            'start_date.after' => 'Start date must be in the future.',
            'end_date.after' => 'End date must be after the start date.',
        ];
    }
}
