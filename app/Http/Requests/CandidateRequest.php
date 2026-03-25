<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'candidate_number' => ['nullable', 'string', 'max:50'],
            'affiliation' => ['required', 'string', 'in:MDP,PNC'],
            'address' => ['nullable', 'string', 'max:255'],
            'race_id' => ['required', 'integer', 'exists:election_races,id'],
        ];
    }
}
