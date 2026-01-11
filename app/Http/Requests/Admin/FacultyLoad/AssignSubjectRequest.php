<?php

namespace App\Http\Requests\Admin\FacultyLoad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate subject assignment to instructor
 */
class AssignSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', [
                        \App\Models\User::ROLE_INSTRUCTOR,
                        \App\Models\User::ROLE_PROGRAM_HEAD,
                        \App\Models\User::ROLE_DEPARTMENT_HEAD,
                    ]);
                }),
            ],
            'subject_id' => 'required|integer|exists:subjects,id',
            'max_sections' => 'required|integer|min:1|max:10',
            'max_load_units' => 'nullable|integer|min:1|max:30',
        ];
    }

    /**
     * Get custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Instructor is required.',
            'user_id.exists' => 'Selected instructor is not eligible for teaching.',
            'subject_id.required' => 'Subject is required.',
            'subject_id.exists' => 'Selected subject does not exist.',
            'max_sections.required' => 'Maximum sections is required.',
            'max_sections.integer' => 'Maximum sections must be a whole number.',
            'max_sections.min' => 'Maximum sections must be at least 1.',
            'max_sections.max' => 'Maximum sections cannot exceed 10.',
            'max_load_units.integer' => 'Maximum load units must be a whole number.',
            'max_load_units.min' => 'Maximum load units must be at least 1.',
            'max_load_units.max' => 'Maximum load units cannot exceed 30.',
        ];
    }
}
