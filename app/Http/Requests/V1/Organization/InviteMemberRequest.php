<?php

declare(strict_types=1);

namespace App\Http\Requests\V1\Organization;

use Illuminate\Foundation\Http\FormRequest;

final class InviteMemberRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'string', 'in:admin,member,viewer'],
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
            'user_id.exists' => 'The specified user does not exist.',
            'role.in' => 'The role must be one of: admin, member, viewer.',
        ];
    }
}
