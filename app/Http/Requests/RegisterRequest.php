<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'],
            'password' => [
                'required', 
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(3)
            ],
            'role' => ['sometimes', 'in:guest,host,admin'],
            'profile_info' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.regex' => 'Name can only contain letters and spaces.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email address is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Role must be guest, host, or admin.',
        ];
    }
}
