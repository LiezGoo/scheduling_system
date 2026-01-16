<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProgramSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();
        return $user && $user->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'program_id' => 'required|integer|exists:programs,id',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
            'year_level' => 'required|integer|between:1,4',
            'semester' => 'required|string|in:1st,2nd,summer',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'program_id.required' => 'Please select a program.',
            'program_id.exists' => 'The selected program is invalid.',
            'subject_ids.required' => 'Please select at least one subject.',
            'subject_ids.min' => 'Please select at least one subject.',
            'subject_ids.*.exists' => 'One or more selected subjects are invalid.',
            'year_level.required' => 'Please select a year level.',
            'year_level.between' => 'Year level must be between 1 and 4.',
            'semester.required' => 'Please select a semester.',
            'semester.in' => 'The selected semester is invalid.',
        ];
    }
}
