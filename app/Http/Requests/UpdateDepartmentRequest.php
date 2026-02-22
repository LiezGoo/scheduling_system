<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user() && Auth::user()->isAdmin();

    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'department_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'department_code')->ignore($this->department->id),
            ],
            'department_name' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'department_code.required' => 'The department code is required.',
            'department_code.unique' => 'This department code already exists.',
            'department_code.max' => 'The department code must not exceed 50 characters.',
            'department_name.required' => 'The department name is required.',
            'department_name.max' => 'The department name must not exceed 255 characters.',
        ];
    }
}
