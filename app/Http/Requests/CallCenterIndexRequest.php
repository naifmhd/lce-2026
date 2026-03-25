<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallCenterIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'cc_filter' => ['nullable', 'string', 'in:filled,blank'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
