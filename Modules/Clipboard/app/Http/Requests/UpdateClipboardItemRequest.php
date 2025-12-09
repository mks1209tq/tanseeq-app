<?php

namespace Modules\Clipboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClipboardItemRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', 'in:text,url,code'],
            'order' => ['nullable', 'integer'],
        ];
    }
}

