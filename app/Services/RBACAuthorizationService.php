<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;

/**
 * RBAC AUTHORIZATION SERVICE
 *
 * Centralized service for role-based access control decisions.
 *
 * Provides query-level scoping, access checks, and authorization decisions
 * that enforce the strict department → program hierarchy.
 *
 * SECURITY: This service should be used in ALL places where data access decisions are made.
 * Frontend filtering MUST NOT be trusted.
 */
class RBACAuthorizationService
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Get a scoped query for departments this user can access.
     *
     * department_head: Only their own department
     * Others: Cannot access any departments
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopedDepartmentsQuery()
    {
        if ($this->user->isDepartmentHead()) {
            return Department::where('id', $this->user->department_id);
        }

        // Return empty query
        return Department::whereNull('id');
    }

    /**
     * Get a scoped query for programs this user can access.
     *
     * department_head: All programs in their department
     * program_head: Only their assigned program
     * instructor/student with program: Only their program
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopedProgramsQuery()
    {
        if ($this->user->isDepartmentHead()) {
            return Program::where('department_id', $this->user->department_id);
        }

        if ($this->user->program_id) {
            return Program::where('id', $this->user->program_id);
        }

        // Return empty query
        return Program::whereNull('id');
    }

    /**
     * Get a scoped query for users this user can access.
     *
     * department_head: All users in their department
     * program_head: Only users in their program
     * Others: Cannot access other users
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopedUsersQuery()
    {
        if ($this->user->isDepartmentHead()) {
            return User::inDepartment($this->user->department_id);
        }

        if ($this->user->isProgramHead()) {
            return User::inProgram($this->user->program_id);
        }

        // Return empty query
        return User::whereNull('id');
    }

    /**
     * Check if user can access a department.
     *
     * @param \App\Models\Department|int $department
     * @return bool
     */
    public function canAccessDepartment($department): bool
    {
        return $this->user->canAccessDepartment($department);
    }

    /**
     * Check if user can access a program.
     *
     * @param \App\Models\Program|int $program
     * @return bool
     */
    public function canAccessProgram($program): bool
    {
        return $this->user->canAccessProgram($program);
    }

    /**
     * Check if user can access another user.
     *
     * @param \App\Models\User $targetUser
     * @return bool
     */
    public function canAccessUser(User $targetUser): bool
    {
        // Users can always access themselves
        if ($this->user->id === $targetUser->id) {
            return true;
        }

        // department_head can access users in their department
        if ($this->user->isDepartmentHead()) {
            return $targetUser->canAccessDepartment($this->user->department_id);
        }

        // program_head can access users in their program
        if ($this->user->isProgramHead()) {
            return $targetUser->program_id === $this->user->program_id;
        }

        return false;
    }

    /**
     * Check if user can manage a resource type.
     *
     * @param string $resourceType (department, program, user, etc.)
     * @return bool
     */
    public function canManageResourceType(string $resourceType): bool
    {
        return match ($resourceType) {
            'department' => $this->user->isDepartmentHead(),
            'program' => $this->user->isDepartmentHead() || $this->user->isProgramHead(),
            'user' => $this->user->isDepartmentHead() || $this->user->isProgramHead(),
            default => false,
        };
    }

    /**
     * Get the authorization context for this user (for frontend display).
     *
     * Returns information about what the user can access.
     *
     * @return array
     */
    public function getAuthorizationContext(): array
    {
        $context = [
            'role' => $this->user->role,
            'role_label' => $this->user->getRoleLabel(),
            'can_manage_departments' => false,
            'can_manage_programs' => false,
            'can_manage_users' => false,
            'assigned_department' => null,
            'assigned_program' => null,
            'accessible_programs' => [],
        ];

        if ($this->user->isDepartmentHead()) {
            $context['can_manage_departments'] = true;
            $context['can_manage_programs'] = true;
            $context['can_manage_users'] = true;
            $context['assigned_department'] = $this->user->department?->only(['id', 'department_code', 'department_name']);
            $context['accessible_programs'] = $this->user->department->programs()
                ->select('id', 'program_code', 'program_name')
                ->get()
                ->toArray();
        }

        if ($this->user->isProgramHead()) {
            $context['can_manage_programs'] = true;
            $context['can_manage_users'] = true;
            $context['assigned_program'] = $this->user->program?->only(['id', 'program_code', 'program_name', 'department_id']);
            if ($this->user->program) {
                $context['accessible_programs'] = [$this->user->program->only(['id', 'program_code', 'program_name'])];
            }
        }

        if ($this->user->isProgramHead() && $this->user->program) {
            $context['assigned_department'] = $this->user->program->department?->only(['id', 'department_code', 'department_name']);
        }

        return $context;
    }
}
