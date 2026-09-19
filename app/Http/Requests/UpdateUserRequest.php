<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $cible = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($cible->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'in:admin,mentor,stagiaire'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
