<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoxResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'registered_box' => ['required', 'string', 'max:255'],
            'invalid_votes' => ['required', 'integer', 'min:0'],
            'races' => ['required', 'array'],
            'races.*.race_id' => ['required', 'integer', 'exists:election_races,id'],
            'races.*.votes' => ['required', 'array'],
            'races.*.votes.*' => ['required', 'integer', 'min:0'],
        ];
    }
}
