<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoterUpdateRequest extends FormRequest
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
            'mobile' => ['nullable', 'string', 'max:255'],
            're_reg_travel' => ['nullable', 'string', 'max:255'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'pledge.mayor' => ['nullable', 'string', Rule::in(['PNC', 'MDP', 'UN', 'NOT VOTING'])],
            'pledge.raeesa' => ['nullable', 'string', Rule::in(['PNC', 'MDP', 'UN', 'NOT VOTING'])],
            'pledge.council' => ['nullable', 'string', Rule::in(['PNC', 'MDP', 'UN', 'NOT VOTING'])],
            'pledge.wdc' => ['nullable', 'string', Rule::in(['PNC', 'MDP', 'UN', 'NOT VOTING'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => $this->emptyStringToNull($this->input('mobile')),
            're_reg_travel' => $this->emptyStringToNull($this->input('re_reg_travel')),
            'comments' => $this->emptyStringToNull($this->input('comments')),
            'pledge' => [
                'mayor' => $this->emptyStringToNull($this->input('pledge.mayor')),
                'raeesa' => $this->emptyStringToNull($this->input('pledge.raeesa')),
                'council' => $this->emptyStringToNull($this->input('pledge.council')),
                'wdc' => $this->emptyStringToNull($this->input('pledge.wdc')),
            ],
        ]);
    }

    private function emptyStringToNull(mixed $value): mixed
    {
        return is_string($value) && trim($value) === '' ? null : $value;
    }
}
