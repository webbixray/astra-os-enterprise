<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Campaign;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCreativeRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:image,video,text,carousel'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'media_url' => ['nullable', 'string', 'url', 'max:2048'],
            'call_to_action' => ['nullable', 'string', 'max:100'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
