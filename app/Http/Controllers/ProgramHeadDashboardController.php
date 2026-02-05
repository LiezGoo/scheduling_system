<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\User;
use App\Services\RBACAuthorizationService;
use Illuminate\Http\Request;

/**
 * PROGRAM HEAD DASHBOARD CONTROLLER
 *
 * Program heads can only manage their assigned program.
 * Cross-program access is strictly prevented.
 *
 * SECURITY PRINCIPLE:
 * - program_head can ONLY access their assigned program
 * - Cannot access sibling programs (e.g., BSIT head cannot access BSCS)
 * - Cannot access department-wide resources
 * - All queries scoped to their single program
 */
class ProgramHeadDashboardController extends Controller
{
    protected RBACAuthorizationService $authService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified.role');
        $this->middleware('account.active');
    }

    /**
     * Show the program head dashboard for their assigned program.
     *
     * SECURITY:
     * - Only accessible to program_head
     * - Program parameter MUST match their assigned program
     * - Cannot view sibling programs
     *
     * @param Request $request
     * @param int $program
     * @return \Illuminate\View\View
     */
    public function show(Request $request, int $program)
    {
        $user = auth()->user();
        $this->authService = new RBACAuthorizationService($user);

        // SECURITY: Verify user is a program head
        if (!$user->isProgramHead()) {
            abort(403, 'Only program heads can access this dashboard.');
        }

        // SECURITY: Verify the program parameter matches their assigned program
        // This is CRITICAL - prevents cross-program access
        if ($user->program_id !== $program) {
            abort(403, 'You do not have permission to access this program.');
        }

        // Load the program with relationships
        $prog = Program::with([
            'departments:id,department_code,department_name',
            'programHead:id,first_name,last_name,email',
        ])->findOrFail($program);

        // Verify authorization
        $this->authorize('view', $prog);

        // QUERY SCOPING: Get users assigned to this program
        $users = User::where('program_id', $program)
            ->select('id', 'first_name', 'last_name', 'email', 'role', 'program_id')
            ->paginate(15);

        // Count stats for this program
        $stats = [
            'instructors' => User::where('program_id', $program)
                ->where('role', User::ROLE_INSTRUCTOR)
                ->count(),
            'students' => User::where('program_id', $program)
                ->where('role', User::ROLE_STUDENT)
                ->count(),
        ];

        return view('program-head.dashboard', [
            'program' => $prog,
            'users' => $users,
            'stats' => $stats,
            'authContext' => $this->authService->getAuthorizationContext(),
        ]);
    }

    /**
     * Show list of users in this program.
     *
     * SECURITY: Only shows users in this program
     *
     * @param Request $request
     * @param int $program
     * @return \Illuminate\View\View
     */
    public function userList(Request $request, int $program)
    {
        $user = auth()->user();

        // SECURITY: Verify program access
        if ($user->program_id !== $program) {
            abort(403, 'You do not have permission to access this program.');
        }

        // QUERY SCOPING: Get only users in this program
        $users = User::where('program_id', $program)
            ->with('program:id,program_name')
            ->paginate(15);

        return view('program-head.users.index', [
            'program_id' => $program,
            'users' => $users,
        ]);
    }

    /**
     * Demonstrate that sibling program access is blocked.
     *
     * Example: BSCS head trying to access BSIT program
     * Result: 403 Forbidden
     *
     * This method is for demonstration purposes.
     * In real usage, requests to unauthorized programs will fail at the route level.
     *
     * @param int $siblingProgram
     * @return void
     */
    public function attemptSiblingAccess(int $siblingProgram)
    {
        $user = auth()->user();

        // This check is CRITICAL for security
        if ($user->program_id !== $siblingProgram) {
            abort(403, 'Cross-program access is not permitted. You can only access your assigned program.');
        }

        // This code should never be reached
        abort(403);
    }
}
