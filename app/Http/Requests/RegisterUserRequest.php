<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'username' => ['nullable', 'string', 'max:255', 'unique:users', 'alpha_dash'],
            'custom_url' => ['nullable', 'string', 'max:255', 'unique:users', 'alpha_dash'],
            'gender' => ['nullable', 'in:male,female,prefer_not_to_say'],
            'birthdate' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'custom_url.unique' => 'This custom URL is already taken. Please choose another.',
            'username.unique' => 'This username is already taken. Please choose another.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores.',
            'custom_url.alpha_dash' => 'Custom URL may only contain letters, numbers, dashes and underscores.',
        ];
    }
}
