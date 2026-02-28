<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
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
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->id : (int) $routeUser;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', Rule::in(UserRole::keys())],
        ];
    }

    protected function prepareForValidation(): void
    {
        $roles = $this->input('roles');

        if (is_array($roles)) {
            $normalizedRoles = array_values(array_unique(array_filter(
                array_map(static fn (mixed $role): string => is_string($role) ? trim($role) : '', $roles),
                static fn (string $role): bool => $role !== '',
            )));

            $this->merge([
                'roles' => $normalizedRoles,
            ]);
        }

        if (is_string($this->input('password')) && trim((string) $this->input('password')) === '') {
            $this->merge([
                'password' => null,
                'password_confirmation' => null,
            ]);
        }
    }
}
