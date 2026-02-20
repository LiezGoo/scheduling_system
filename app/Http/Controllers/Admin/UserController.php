<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users with optional filters.
     */
    public function index(Request $request)
    {
        $query = User::with(['department', 'program']);

        // Filter by name (search)
        if ($request->filled('name')) {
            $query->where(function ($subQuery) use ($request) {
                $search = '%' . $request->name . '%';
                $subQuery->where('first_name', 'LIKE', $search)
                    ->orWhere('last_name', 'LIKE', $search)
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$search]);
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered users
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage)->appends($request->query());

        // Get all roles for filter dropdown
        $roles = User::getAllRoles();

        // Get departments and programs for assignment dropdowns
        $departments = Department::orderBy('department_name')->get();
        $programs = Program::with('department')->orderBy('program_name')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'rows' => view('admin.users.partials.table-rows', compact('users'))->render(),
                'pagination' => view('admin.users.partials.pagination', compact('users'))->render(),
                'summary' => view('admin.users.partials.summary', compact('users'))->render(),
            ]);
        }

        return view('admin.users.index', compact('users', 'roles', 'departments', 'programs'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,department_head,program_head,instructor,student',
            'department_id' => 'nullable|exists:departments,id',
            'program_id' => 'nullable|exists:programs,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Validate organizational scoping rules
        $this->validateOrganizationalScoping($validated['role'], $request);

        // Keep is_active synchronized with status
        $validated['is_active'] = ($validated['status'] === User::STATUS_ACTIVE);

        // Clear department/program if not applicable to role
        if (!in_array($validated['role'], [User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])) {
            $validated['department_id'] = null;
            $validated['program_id'] = null;
        } elseif ($validated['role'] === User::ROLE_DEPARTMENT_HEAD) {
            $validated['program_id'] = null;
        } elseif ($validated['role'] === User::ROLE_PROGRAM_HEAD) {
            $validated['department_id'] = null;
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'status' => $validated['status'],
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully!'
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,department_head,program_head,instructor,student',
            'department_id' => 'nullable|exists:departments,id',
            'program_id' => 'nullable|exists:programs,id',
            'status' => 'required|string|in:active,inactive',
        ]);

        // Validate organizational scoping rules
        $this->validateOrganizationalScoping($validated['role'], $request, $user->id);

        $updateData = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'program_id' => $validated['program_id'] ?? null,
            'status' => $validated['status'],
            'is_active' => ($validated['status'] === User::STATUS_ACTIVE),
        ];

        if ($validated['role'] === User::ROLE_STUDENT) {
            $updateData['department_id'] = null;
            $updateData['program_id'] = null;
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully!'
        ]);
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(User $user)
    {
        try {
            // Prevent admin from deactivating themselves
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account!'
                ], 403);
            }

            $newStatus = $user->status === User::STATUS_ACTIVE
                ? User::STATUS_INACTIVE
                : User::STATUS_ACTIVE;

            // Update both status and is_active atomically
            $user->update([
                'status' => $newStatus,
                'is_active' => ($newStatus === User::STATUS_ACTIVE),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully!',
                'status' => $newStatus,
                'is_active' => $user->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Status toggle failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status. Please try again.'
            ], 500);
        }
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy(User $user)
    {
        try {
            // Prevent admin from deleting themselves
            if ($user->id === Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account!'
                ], 403);
            }

            // Force permanent deletion (bypass soft deletes)
            $user->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted permanently!'
            ]);
        } catch (\Exception $e) {
            Log::error('User deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user. Please try again.'
            ], 500);
        }
    }

    /**
     * Validate organizational scoping rules for department and program assignments.
     *
     * Business Rules:
     * 1. Department Heads MUST have a department_id assigned
     * 2. Program Heads MUST have a program_id assigned
    * 3. Students do not require program assignment
    * 4. Other roles CANNOT have department_id or program_id
    * 5. Only ONE user can be assigned as head of a department/program
     *
     * @throws \Exception
     */
    private function validateOrganizationalScoping(string $role, Request $request, ?int $excludeUserId = null)
    {
        if ($role === User::ROLE_DEPARTMENT_HEAD) {
            // Department Head must have a department assigned
            if (!$request->filled('department_id')) {
                throw new \Exception('Department Heads must be assigned to a specific department.');
            }

            // Check if department already has a head assigned (excluding current user if updating)
            $existingHead = User::where('role', User::ROLE_DEPARTMENT_HEAD)
                               ->where('department_id', $request->department_id)
                               ->when($excludeUserId, function($query) use ($excludeUserId) {
                                   return $query->where('id', '!=', $excludeUserId);
                               })
                               ->first();

            if ($existingHead) {
                $department = Department::find($request->department_id);
                throw new \Exception(
                    "The department '{$department->department_name}' already has a Department Head assigned: {$existingHead->full_name}"
                );
            }
        }

        if ($role === User::ROLE_PROGRAM_HEAD) {
            // Program Head must have a program assigned
            if (!$request->filled('program_id')) {
                throw new \Exception('Program Heads must be assigned to a specific program.');
            }

            // Check if program already has a head assigned (excluding current user if updating)
            $existingHead = User::where('role', User::ROLE_PROGRAM_HEAD)
                               ->where('program_id', $request->program_id)
                               ->when($excludeUserId, function($query) use ($excludeUserId) {
                                   return $query->where('id', '!=', $excludeUserId);
                               })
                               ->first();

            if ($existingHead) {
                $program = Program::find($request->program_id);
                throw new \Exception(
                    "The program '{$program->program_name}' already has a Program Head assigned: {$existingHead->full_name}"
                );
            }
        }

        if ($role === User::ROLE_STUDENT && $request->filled('program_id')) {
            $program = Program::find($request->program_id);
            if (!$program) {
                throw new \Exception('Selected program does not exist.');
            }
        }
    }

    /**
     * Get user details for editing.
     */
    public function show(User $user)
    {
        return response()->json([
            'success' => true,
            'user' => $user->load(['department', 'program'])
        ]);
    }
}
