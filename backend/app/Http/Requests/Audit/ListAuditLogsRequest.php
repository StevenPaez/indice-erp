<?php

namespace App\Http\Requests\Audit;

use App\Enums\AuditEvent;
use App\Enums\Capability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Capability::AuditView->value) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event' => $this->filled('event') ? trim((string) $this->input('event')) : null,
            'request_id' => $this->filled('request_id') ? trim((string) $this->input('request_id')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'event' => ['nullable', Rule::enum(AuditEvent::class)],
            'actor_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'request_id' => ['nullable', 'string', 'max:100', 'regex:/\A[a-zA-Z0-9-]+\z/'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'event.enum' => 'El evento seleccionado no es válido.',
            'actor_id.exists' => 'El actor seleccionado no existe.',
            'request_id.regex' => 'El identificador de solicitud no es válido.',
            'date_from.date_format' => 'La fecha inicial no es válida.',
            'date_to.date_format' => 'La fecha final no es válida.',
            'date_to.after_or_equal' => 'La fecha final debe ser igual o posterior a la inicial.',
        ];
    }
}
