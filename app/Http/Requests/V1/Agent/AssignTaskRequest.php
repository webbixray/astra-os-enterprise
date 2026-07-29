<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Agent;

use Illuminate\Foundation\Http\FormRequest;

final class AssignTaskRequest extends FormRequest
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
            'type' => ['required', 'string', 'in:analysis,generation,research,review,classification,sub_task'],
            'input' => ['required', 'array'],
            'parent_task_id' => ['nullable', 'integer', 'exists:agent_tasks,id'],
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
            'type.in' => 'Invalid task type. Valid types: analysis, generation, research, review, classification, sub_task.',
        ];
    }
}
