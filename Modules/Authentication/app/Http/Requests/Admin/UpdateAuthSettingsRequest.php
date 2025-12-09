<?php

namespace Modules\Authentication\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAuthSettingsRequest extends FormRequest
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
            'require_email_verification' => ['nullable', 'boolean'],
            'force_two_factor' => ['nullable', 'boolean'],
            'allow_registration' => ['nullable', 'boolean'],
            'password_min_length' => ['nullable', 'integer', 'min:6'],
            'session_lifetime' => ['nullable', 'integer', 'min:1'],
            'max_login_attempts' => ['nullable', 'integer', 'min:1'],
            'lockout_duration' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

