<?php

use App\Models\User;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Support\Facades\Gate;

/**
 * LARAVEL GATES FOR RBAC
 *
 * Gates provide a way to define authorization checks that don't fit into policies.
 * These gates complement the Policy-based authorization for specific business rules.
 *
 * Location: Register these in AuthServiceProvider@boot()
 *
 * Usage in controllers/views:
 *     if (Gate::allows('manage-department-users', $department)) { ... }
 *     @can('manage-programs', $department)
 */

// ========================================
// DEPARTMENT GATES
// ========================================

Gate::define('view-department', function (User $user, Department $department) {
    return $user->canAccessDepartment($department);
});

Gate::define('manage-department', function (User $user, Department $department) {
    return $user->isDepartmentHead() && $user->department_id === $department->id;
});

Gate::define('manage-department-users', function (User $user, Department $department) {
    return $user->isDepartmentHead() && $user->department_id === $department->id;
});

Gate::define('manage-department-programs', function (User $user, Department $department) {
    return $user->isDepartmentHead() && $user->department_id === $department->id;
});

// ========================================
// PROGRAM GATES
// ========================================

Gate::define('view-program', function (User $user, Program $program) {
    return $user->canAccessProgram($program);
});

Gate::define('manage-program', function (User $user, Program $program) {
    if ($user->isDepartmentHead()) {
        return $program->department_id === $user->department_id;
    }

    if ($user->isProgramHead()) {
        return $program->id === $user->program_id;
    }

    return false;
});

Gate::define('manage-program-users', function (User $user, Program $program) {
    return $user->isDepartmentHead() ||
           ($user->isProgramHead() && $program->id === $user->program_id);
});

Gate::define('manage-program-schedule', function (User $user, Program $program) {
    return $user->isDepartmentHead() ||
           ($user->isProgramHead() && $program->id === $user->program_id);
});

Gate::define('manage-program-curriculum', function (User $user, Program $program) {
    return $user->isDepartmentHead() ||
           ($user->isProgramHead() && $program->id === $user->program_id);
});

// ========================================
// USER GATES
// ========================================

Gate::define('view-user', function (User $user, User $targetUser) {
    return $user->id === $targetUser->id ||
           ($user->isDepartmentHead() && $targetUser->canAccessDepartment($user->department_id)) ||
           ($user->isProgramHead() && $targetUser->program_id === $user->program_id);
});

Gate::define('manage-user', function (User $user, User $targetUser) {
    return $user->id !== $targetUser->id &&
           ($user->isDepartmentHead() && $targetUser->canAccessDepartment($user->department_id) ||
            $user->isProgramHead() && $targetUser->program_id === $user->program_id);
});

Gate::define('assign-role', function (User $user, User $targetUser, string $role) {
    // Only department heads can assign roles
    if (!$user->isDepartmentHead()) {
        return false;
    }

    // Can only assign roles to users in their department
    if (!$targetUser->canAccessDepartment($user->department_id)) {
        return false;
    }

    // Cannot promote to department_head
    if ($role === User::ROLE_DEPARTMENT_HEAD) {
        return false;
    }

    // Valid role
    return in_array($role, User::getAllRoles());
});

Gate::define('activate-user', function (User $user, User $targetUser) {
    return $user->isDepartmentHead() && $targetUser->canAccessDepartment($user->department_id) ||
           $user->isProgramHead() && $targetUser->program_id === $user->program_id;
});

Gate::define('deactivate-user', function (User $user, User $targetUser) {
    return $user->isDepartmentHead() && $targetUser->canAccessDepartment($user->department_id) ||
           $user->isProgramHead() && $targetUser->program_id === $user->program_id;
});

// ========================================
// ROLE-BASED FEATURE GATES
// ========================================

Gate::define('is-department-head', function (User $user) {
    return $user->isDepartmentHead();
});

Gate::define('is-program-head', function (User $user) {
    return $user->isProgramHead();
});

Gate::define('is-instructor', function (User $user) {
    return $user->role === User::ROLE_INSTRUCTOR;
});

Gate::define('is-student', function (User $user) {
    return $user->role === User::ROLE_STUDENT;
});

// ========================================
// DASHBOARD GATES
// ========================================

Gate::define('access-admin-dashboard', function (User $user) {
    return $user->isDepartmentHead();
});

Gate::define('access-program-head-dashboard', function (User $user) {
    return $user->isProgramHead();
});

Gate::define('access-instructor-dashboard', function (User $user) {
    return in_array($user->role, [User::ROLE_INSTRUCTOR, User::ROLE_PROGRAM_HEAD, User::ROLE_DEPARTMENT_HEAD]);
});

Gate::define('access-student-dashboard', function (User $user) {
    return $user->role === User::ROLE_STUDENT;
});
