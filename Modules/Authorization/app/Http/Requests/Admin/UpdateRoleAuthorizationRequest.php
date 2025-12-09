<?php

namespace Modules\Authorization\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleAuthorizationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'auth_object_id' => ['required', 'exists:authorization.auth_objects,id'],
            'label' => ['nullable', 'string', 'max:255'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.field_code' => ['required', 'string', 'max:255'],
            'fields.*.operator' => ['required', 'string', Rule::in(['*', '=', 'in', 'between'])],
            'fields.*.value_from' => ['nullable', 'string', 'max:255'],
            'fields.*.value_to' => ['nullable', 'string', 'max:255', 'required_if:fields.*.operator,between'],
        ];
    }
}

