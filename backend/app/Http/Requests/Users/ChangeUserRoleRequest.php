<?php

namespace App\Http\Requests\Users;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeRole', $this->route('user')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => strtolower(trim((string) $this->input('role'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'El rol es obligatorio.',
            'role.enum' => 'El rol seleccionado no es válido.',
        ];
    }
}
