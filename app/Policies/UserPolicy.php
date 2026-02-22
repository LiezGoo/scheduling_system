<?php

namespace App\Policies;

use App\Models\User;

/**
 * USER POLICY
 *
 * Enforces strict role-based user access control:
 *
 * department_head: Can manage all users in their department
 * program_head: Can manage only users in their program
 * Others: Cannot manage any user
 *
 * SECURITY: User assignment is tightly coupled to organizational hierarchy.
 */
class UserPolicy
{
    /**
     * Determine if user can view other users.
     *
     * department_head: Can view all users in their department
     * program_head: Can view users in their program
     * Others: Cannot view other users (except themselves)
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $targetUser
     * @return bool
     */
    public function view(User $user, User $targetUser): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Users can always view themselves
        if ($user->id === $targetUser->id) {
            return true;
        }

        // department_head: can view all users in their department
        if ($user->isDepartmentHead()) {
            return $targetUser->canAccessDepartment($user->department_id);
        }

        // program_head: can view users in their program
        if ($user->isProgramHead()) {
            return $targetUser->program_id === $user->program_id;
        }

        return false;
    }

    /**
     * Determine if user can create a user.
     *
     * department_head: Can create users for their department
     * Others: Cannot create users
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDepartmentHead();
    }

    /**
     * Determine if user can update a user.
     *
     * department_head: Can update all users in their department
     * program_head: Can update users in their program
     * Others: Cannot update any user (except themselves in limited ways)
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $targetUser
     * @return bool
     */
    public function update(User $user, User $targetUser): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Users can update their own profile (limited fields via form request)
        if ($user->id === $targetUser->id) {
            return true;
        }

        // SECURITY: department_head can update users in their department
        if ($user->isDepartmentHead()) {
            return $targetUser->canAccessDepartment($user->department_id);
        }

        // SECURITY: program_head can update users in their program
        if ($user->isProgramHead()) {
            return $targetUser->program_id === $user->program_id;
        }

        return false;
    }

    /**
     * Determine if user can delete (deactivate) a user.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $targetUser
     * @return bool
     */
    public function delete(User $user, User $targetUser): bool
    {
        if ($user->isAdmin()) {
            return $user->id !== $targetUser->id;
        }

        // Cannot delete oneself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // department_head: can deactivate users in their department
        if ($user->isDepartmentHead()) {
            return $targetUser->canAccessDepartment($user->department_id);
        }

        // program_head: can deactivate users in their program
        if ($user->isProgramHead()) {
            return $targetUser->program_id === $user->program_id;
        }

        return false;
    }

    /**
     * Determine if user can restore a user.
     */
    public function restore(User $user, User $targetUser): bool
    {
        return $this->delete($user, $targetUser);
    }

    /**
     * Determine if user can permanently delete a user.
     */
    public function forceDelete(User $user, User $targetUser): bool
    {
        return false;
    }

    /**
     * SPECIAL GATE: Can user assign/modify another user's role?
     *
     * This is separate from update() because role assignment has stricter rules.
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $targetUser
     * @param string $newRole
     * @return bool
     */
    public function assignRole(User $user, User $targetUser, string $newRole): bool
    {
        // Cannot assign roles to oneself
        if ($user->id === $targetUser->id) {
            return false;
        }

        if ($user->isAdmin()) {
            return in_array($newRole, User::getAllRoles());
        }

        // Only department heads can assign roles
        if (!$user->isDepartmentHead()) {
            return false;
        }

        // Can only assign roles to users in their department
        if (!$targetUser->canAccessDepartment($user->department_id)) {
            return false;
        }

        // SECURITY: Validate the new role is valid
        if (!in_array($newRole, User::getAllRoles())) {
            return false;
        }

        // SECURITY: Cannot promote someone to department_head
        // (Only system can assign department heads)
        if ($newRole === User::ROLE_DEPARTMENT_HEAD) {
            return false;
        }

        return true;
    }

    /**
     * SPECIAL GATE: Can user assign department/program to another user?
     *
     * @param \App\Models\User $user
     * @param \App\Models\User $targetUser
     * @return bool
     */
    public function assignOrganization(User $user, User $targetUser): bool
    {
        // Cannot assign organization to oneself
        if ($user->id === $targetUser->id) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Only department heads can assign organization
        if (!$user->isDepartmentHead()) {
            return false;
        }

        // Can only assign organization to users in their department
        return $targetUser->canAccessDepartment($user->department_id);
    }
}
