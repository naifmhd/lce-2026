<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoterIndexRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'dhaairaa' => ['nullable', 'string', 'max:255'],
            'majilis_con' => ['nullable', 'string', 'max:255'],
            'selected' => ['nullable', 'integer', 'exists:voter_records,id'],
        ];
    }
}
