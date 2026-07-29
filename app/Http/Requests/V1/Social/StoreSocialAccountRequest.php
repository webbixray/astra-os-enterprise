<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Social;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSocialAccountRequest extends FormRequest
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
            'platform' => ['required', 'string', 'in:twitter,linkedin,facebook,instagram'],
            'account_id' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'access_token' => ['nullable', 'string'],
            'refresh_token' => ['nullable', 'string'],
            'token_expires_at' => ['nullable', 'date'],
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
            'platform.in' => 'Invalid platform. Valid platforms: twitter, linkedin, facebook, instagram.',
        ];
    }
}
