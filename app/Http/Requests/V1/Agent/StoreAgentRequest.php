<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Agent;

use Illuminate\Foundation\Http\FormRequest;

final class StoreAgentRequest extends FormRequest
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
            'role' => ['required', 'string', 'in:ceo,director,manager,specialist,custom'],
            'description' => ['nullable', 'string', 'max:2000'],
            'model' => ['required', 'string', 'in:gpt-4o,gpt-4o-mini,gpt-3.5-turbo,claude-3-opus,claude-3-sonnet,claude-3-haiku,gemini-pro,gemini-flash'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string'],
            'configuration' => ['nullable', 'array'],
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
            'role.in' => 'Invalid role. Valid roles: ceo, director, manager, specialist, custom.',
            'model.in' => 'Invalid model. Please select a supported AI model.',
        ];
    }
}
