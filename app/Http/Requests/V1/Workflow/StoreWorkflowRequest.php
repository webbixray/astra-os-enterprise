<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Workflow;

use Illuminate\Foundation\Http\FormRequest;

final class StoreWorkflowRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:5000'],
            'nodes' => ['required', 'array', 'min:1'],
            'nodes.*.id' => ['required', 'string', 'max:100'],
            'nodes.*.type' => ['required', 'string', 'in:action,condition,trigger,output'],
            'nodes.*.config' => ['required', 'array'],
            'edges' => ['required', 'array'],
            'edges.*.from' => ['required', 'string'],
            'edges.*.to' => ['required', 'string'],
            'triggers' => ['nullable', 'array'],
            'triggers.*.event' => ['string'],
            'variables' => ['nullable', 'array'],
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
            'nodes.*.type.in' => 'Invalid node type. Valid types: action, condition, trigger, output.',
            'nodes.min' => 'Workflow must have at least one node.',
        ];
    }
}
