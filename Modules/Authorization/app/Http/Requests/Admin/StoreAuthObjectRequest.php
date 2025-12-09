<?php

namespace Modules\Authorization\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthObjectRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', 'unique:authorization.auth_objects,code'],
            'description' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*.code' => ['required', 'string', 'max:255'],
            'fields.*.label' => ['nullable', 'string', 'max:255'],
            'fields.*.is_org_level' => ['nullable', 'boolean'],
            'fields.*.sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

