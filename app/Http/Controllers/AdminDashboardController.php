<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Services\RBACAuthorizationService;
use Illuminate\Http\Request;

/**
 * ADMIN DASHBOARD CONTROLLER (Department Head)
 *
 * Implements a SINGLE admin dashboard that enforces strict scoping to the department head's department.
 *
 * SECURITY PRINCIPLE:
 * - One unified admin dashboard view
 * - Scoping enforced at QUERY LEVEL
 * - Department parameter is VERIFIED against authenticated user's department
 * - No cross-department access possible
 */
class AdminDashboardController extends Controller
{
    protected RBACAuthorizationService $authService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified.role');
        $this->middleware('account.active');
    }

    /**
     * Show the admin dashboard for a specific department.
     *
     * SECURITY:
     * - Only accessible to department_head
     * - Department parameter is verified against authenticated user's department
     * - All data queries are automatically scoped to this department
     *
     * @param Request $request
     * @param int $department
     * @return \Illuminate\View\View
     */
    public function show(Request $request, int $department)
    {
        $user = auth()->user();
        $this->authService = new RBACAuthorizationService($user);

        // SECURITY: Verify user is a department head
        if (!$user->isDepartmentHead()) {
            abort(403, 'Only department heads can access the admin dashboard.');
        }

        // SECURITY: Verify the department parameter matches their assigned department
        if ($user->department_id !== $department) {
            abort(403, 'You do not have permission to access this department.');
        }

        // Load the department with relationships
        $dept = Department::with(['programs' => function ($q) {
            $q->select('id', 'program_code', 'program_name', 'department_id');
        }])->findOrFail($department);

        // Verify the department exists and is accessible
        $this->authorize('view', $dept);

        // QUERY SCOPING: Get all programs in this department
        $programs = $this->authService->scopedProgramsQuery()->get();

        // QUERY SCOPING: Get all users in this department
        $users = $this->authService->scopedUsersQuery()
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'program_id', 'department_id')
            ->paginate(15);

        // Count stats for dashboard
        $stats = [
            'total_programs' => $programs->count(),
            'total_users' => User::inDepartment($department)->count(),
            'program_heads' => User::inDepartment($department)
                ->where('role', User::ROLE_PROGRAM_HEAD)
                ->count(),
            'instructors' => User::inDepartment($department)
                ->where('role', User::ROLE_INSTRUCTOR)
                ->count(),
            'students' => User::inDepartment($department)
                ->where('role', User::ROLE_STUDENT)
                ->count(),
        ];

        return view('admin.dashboard', [
            'department' => $dept,
            'programs' => $programs,
            'users' => $users,
            'stats' => $stats,
            'authContext' => $this->authService->getAuthorizationContext(),
        ]);
    }

    /**
     * Show list of all departments accessible to the user.
     *
     * For department heads: Shows only their department
     * For others: 403 Forbidden
     *
     * @return \Illuminate\View\View
     */
    public function departmentList()
    {
        $user = auth()->user();

        if (!$user->isDepartmentHead()) {
            abort(403, 'Only department heads can access the department list.');
        }

        $departments = $user->department ? [$user->department] : [];

        return view('admin.departments.index', [
            'departments' => $departments,
        ]);
    }

    /**
     * Show list of all programs in the department.
     *
     * SECURITY: All programs are scoped to the authenticated user's department
     *
     * @param Request $request
     * @param int $department
     * @return \Illuminate\View\View
     */
    public function programList(Request $request, int $department)
    {
        $user = auth()->user();
        $this->authService = new RBACAuthorizationService($user);

        // SECURITY: Verify department access
        if ($user->department_id !== $department) {
            abort(403, 'You do not have permission to access this department.');
        }

        // QUERY SCOPING: Get all programs in this department
        $programs = $this->authService->scopedProgramsQuery()
            ->with('programHead:id,first_name,last_name,email')
            ->paginate(10);

        return view('admin.programs.index', [
            'department_id' => $department,
            'programs' => $programs,
        ]);
    }

    /**
     * Show list of all users in the department.
     *
     * SECURITY: All users are scoped to the authenticated user's department
     *
     * @param Request $request
     * @param int $department
     * @return \Illuminate\View\View
     */
    public function userList(Request $request, int $department)
    {
        $user = auth()->user();
        $this->authService = new RBACAuthorizationService($user);

        // SECURITY: Verify department access
        if ($user->department_id !== $department) {
            abort(403, 'You do not have permission to access this department.');
        }

        // QUERY SCOPING: Get all users in this department
        $users = $this->authService->scopedUsersQuery()
            ->with(['department:id,department_name', 'program:id,program_name'])
            ->paginate(15);

        return view('admin.users.index', [
            'department_id' => $department,
            'users' => $users,
        ]);
    }
}
