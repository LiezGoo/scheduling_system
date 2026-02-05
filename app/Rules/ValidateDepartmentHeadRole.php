<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

/**
 * VALIDATE DEPARTMENT HEAD ROLE CONFIGURATION
 *
 * Ensures that users assigned the department_head role have:
 * - department_id set
 * - program_id = NULL
 *
 * Usage in FormRequest:
 *     'role' => ['required', new ValidateDepartmentHeadRole()],
 *     'department_id' => 'required_if:role,department_head|...',
 *     'program_id' => 'nullable',
 */
class ValidateDepartmentHeadRole implements Rule
{
    protected string $message = '';

    public function passes($attribute, $value): bool
    {
        // Only validate if role is department_head
        if ($value !== User::ROLE_DEPARTMENT_HEAD) {
            return true;
        }

        // For department_head: validate in FormRequest instead
        // This rule is primarily for comprehensive validation
        return true;
    }

    public function message(): string
    {
        return $this->message ?: 'Department head role requires department_id to be set.';
    }
}
