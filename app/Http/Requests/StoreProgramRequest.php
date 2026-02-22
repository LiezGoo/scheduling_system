<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'program_code' => 'required|string|max:50|unique:programs,program_code',
            'program_name' => 'required|string|max:255',
            'department_id' => 'required|integer|exists:departments,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'program_code.required' => 'The program code is required.',
            'program_code.unique' => 'This program code already exists.',
            'program_name.required' => 'The program name is required.',
            'department_id.required' => 'Please select a department.',
            'department_id.exists' => 'The selected department is invalid.',
        ];
    }
}
