<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * CREATE/UPDATE USER FORM REQUEST
 *
 * Enforces RBAC constraints at the form request level:
 *
 * - department_head: MUST have department_id, MUST NOT have program_id
 * - program_head: MUST have program_id, cannot have program outside their dept
 * - student: MUST have program_id
 * - instructor: flexible
 *
 * SECURITY: Form requests are the first line of validation.
 * These rules prevent invalid state from reaching the controller.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() &&
               (auth()->user()->isAdmin() || auth()->user()->isDepartmentHead() || auth()->user()->isProgramHead());
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => [
                'required',
                Rule::in(User::getAllRoles()),
            ],
            'department_id' => [
                'nullable',
                'exists:departments,id',
                // If role is department_head, department_id is required
                Rule::requiredIf(function () {
                    return $this->input('role') === User::ROLE_DEPARTMENT_HEAD;
                }),
            ],
            'program_id' => [
                'nullable',
                'exists:programs,id',
                // If role is program_head or student, program_id is required
                Rule::requiredIf(function () {
                    return in_array($this->input('role'), [
                        User::ROLE_PROGRAM_HEAD,
                        User::ROLE_STUDENT,
                    ]);
                }),
                // If department_head, program_id must be NULL
                Rule::prohibitedIf(function () {
                    return $this->input('role') === User::ROLE_DEPARTMENT_HEAD;
                }),
                // If program_head, verify program belongs to their department
                $this->validateProgramBelongsToDepartment(),
            ],
            'faculty_scheme' => [
                'nullable',
                Rule::in(['7:00-16:00', '8:00-17:00', '10:00-19:00']),
            ],
            'status' => [
                'required',
                Rule::in(User::STATUS_ACTIVE, User::STATUS_INACTIVE),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required_if' => 'Department head role requires a department assignment.',
            'program_id.required_if' => 'Program head and student roles require a program assignment.',
            'program_id.prohibited_if' => 'Department head cannot have a program assignment.',
            'role.in' => 'Invalid role. Allowed roles: ' . implode(', ', User::getAllRoles()),
            'faculty_scheme.in' => 'Faculty scheme must be one of: 7:00-16:00, 8:00-17:00, 10:00-19:00',
        ];
    }

    /**
     * Validate that assigned program belongs to the user's department (if program_head).
     *
     * This is a closure rule that prevents cross-department program assignment.
     *
     * @return \Closure|null
     */
    private function validateProgramBelongsToDepartment()
    {
        return function ($attribute, $value, $fail) {
            $role = $this->input('role');
            $departmentId = $this->input('department_id');

            // Only validate if role is program_head
            if ($role !== User::ROLE_PROGRAM_HEAD || !$value) {
                return;
            }

            // Get the program's department
            $program = \App\Models\Program::find($value);
            if (!$program) {
                $fail('The selected program does not exist.');
                return;
            }

            // Verify program belongs to the specified department
            if ($program->department_id !== $departmentId) {
                $fail('The program must belong to the assigned department.');
            }
        };
    }
}
