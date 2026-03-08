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
            'registered_box' => ['nullable', 'string', 'max:255'],
            'agent' => ['nullable', 'string', 'max:255'],
            'age_from' => ['nullable', 'integer', 'min:0', 'max:150'],
            'age_to' => ['nullable', 'integer', 'min:0', 'max:150'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'council_pledge' => ['nullable', 'string', 'max:255'],
            'wdc_pledge' => ['nullable', 'string', 'max:255'],
            'mayor_pledge' => ['nullable', 'string', 'max:255'],
            'raeesa_pledge' => ['nullable', 'string', 'max:255'],
        ];
    }
}
