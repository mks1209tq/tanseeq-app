<?php

namespace Modules\Authorization\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAuthObjectRequest extends FormRequest
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
        $authObjectId = $this->route('auth_object');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('auth_objects', 'code')->connection('authorization')->ignore($authObjectId)],
            'description' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*.code' => ['required', 'string', 'max:255'],
            'fields.*.label' => ['nullable', 'string', 'max:255'],
            'fields.*.is_org_level' => ['nullable', 'boolean'],
            'fields.*.sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

