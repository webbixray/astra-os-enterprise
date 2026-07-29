<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Social;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSocialPostRequest extends FormRequest
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
            'social_account_id' => ['required', 'integer', 'exists:social_accounts,id'],
            'content' => ['required', 'string', 'max:5000'],
            'media_url' => ['nullable', 'string', 'url', 'max:2048'],
            'platforms' => ['required', 'array', 'min:1'],
            'platforms.*' => ['string', 'in:twitter,linkedin,facebook,instagram'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
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
            'platforms.*.in' => 'Invalid platform. Valid platforms: twitter, linkedin, facebook, instagram.',
            'content.max' => 'Post content must not exceed 5000 characters.',
            'scheduled_at.after' => 'Schedule time must be in the future.',
        ];
    }
}
