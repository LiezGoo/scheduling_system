<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone not logged in can register
        return $this->user() === null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc,dns',
                Rule::unique('users', 'email'),
                function ($attribute, $value, $fail) {
                    // Enforce university email domain
                    $allowedDomains = config('auth.allowed_email_domains', ['sorsu.edu.ph']);
                    $emailDomain = substr(strrchr($value, "@"), 1);
                    
                    if (!in_array($emailDomain, $allowedDomains)) {
                        $fail("You must register with a {$allowedDomains[0]} email address.");
                    }
                },
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['instructor', 'student', 'program_head', 'department_head']),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',      // At least one lowercase letter
                'regex:/[A-Z]/',      // At least one uppercase letter
                'regex:/[0-9]/',      // At least one digit
                'regex:/[@$!%*?&]/',  // At least one special character
            ],
            'password_confirmation' => ['required'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already registered.',
            'email.email' => 'Please enter a valid email address.',
            'password.min' => 'Password must be at least 8 characters long.',
            'password.regex' => 'Password must contain uppercase, lowercase, numbers, and special characters (@$!%*?&).',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.in' => 'Please select a valid role.',
            'terms.accepted' => 'You must agree to the Terms and Conditions and Privacy Policy.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower($this->email),
        ]);
    }
}
