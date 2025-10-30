<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['nullable','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Este email ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
