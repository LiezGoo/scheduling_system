<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subject;
use App\Models\Program;
use App\Services\FacultyLoadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Faculty Load Management Controller
 *
 * Handles all HTTP requests for Faculty Load Management.
 * This module manages what subjects instructors are eligible to teach
 * and their load constraints (max_sections, max_load_units).
 *
 * Strictly independent from scheduling logic.
 */
class FacultyLoadController extends Controller
{
    protected FacultyLoadService $facultyLoadService;

    public function __construct(FacultyLoadService $facultyLoadService)
    {
        $this->facultyLoadService = $facultyLoadService;
    }

    /**
     * Display Faculty Load Management dashboard with eligible instructors and assignments.
     */
    public function index(Request $request)
    {
        // Build faculty loads query (flattened view of all faculty-subject assignments)
        $query = DB::table('faculty_subjects')
                    ->join('users', 'faculty_subjects.user_id', '=', 'users.id')
                    ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
                    ->join('programs', 'subjects.program_id', '=', 'programs.id')
                    ->select('faculty_subjects.*', 'users.full_name', 'users.school_id', 'users.role',
                             'subjects.subject_code', 'subjects.subject_name', 'subjects.units',
                             'programs.program_name', 'programs.id as program_id')
                    ->where('users.status', 'active')
                    ->whereIn('users.role', [User::ROLE_INSTRUCTOR, User::ROLE_PROGRAM_HEAD, User::ROLE_DEPARTMENT_HEAD]);

        // Filter by faculty name or ID
        if ($request->filled('faculty')) {
            $search = '%' . $request->input('faculty') . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('users.full_name', 'LIKE', $search)
                         ->orWhere('users.school_id', 'LIKE', $search);
            });
        }

        // Filter by program
        if ($request->filled('program')) {
            $query->where('programs.id', $request->input('program'));
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('users.role', $request->input('role'));
        }

        // Filter by subject
        if ($request->filled('subject')) {
            $query->where('subjects.id', $request->input('subject'));
        }

        // Get per page value
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Paginate faculty loads
        $facultyLoads = $query->orderBy('users.first_name')
                              ->orderBy('users.last_name')
                              ->paginate($perPage)
                              ->appends($request->query());

        // Get programs for filter dropdown
        $programs = Program::orderBy('program_name')->get();

        // Get subjects for filter dropdown
        $subjects = Subject::orderBy('subject_code')->get();

        // Get eligible faculty for assignment modal
        $eligibleFaculty = User::eligibleInstructors()->active()
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get();

        // Get summary statistics
        $summary = $this->facultyLoadService->getFacultyLoadSummary();

        return view('admin.faculty_load.index', [
            'facultyLoads' => $facultyLoads,
            'programs' => $programs,
            'subjects' => $subjects,
            'eligibleFaculty' => $eligibleFaculty,
            'summary' => $summary,
            'currentFilters' => [
                'faculty' => $request->input('faculty'),
                'program' => $request->input('program'),
                'role' => $request->input('role'),
                'subject' => $request->input('subject'),
            ],
        ]);
    }

    /**
     * Get faculty load details as JSON (for modals).
     */
    public function getDetails($facultyLoadId)
    {
        try {
            $load = DB::table('faculty_subjects')
                       ->join('users', 'faculty_subjects.user_id', '=', 'users.id')
                       ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
                       ->join('programs', 'subjects.program_id', '=', 'programs.id')
                       ->select('faculty_subjects.*', 'users.id as user_id', 'users.full_name', 'users.school_id', 'users.role',
                                'subjects.id as subject_id', 'subjects.subject_code', 'subjects.subject_name', 'subjects.units',
                                'programs.id as program_id', 'programs.program_name')
                       ->where('faculty_subjects.id', $facultyLoadId)
                       ->first();

            if (!$load) {
                return response()->json(['success' => false, 'message' => 'Faculty load not found'], 404);
            }

            return response()->json([
                'success' => true,
                'id' => $load->id,
                'faculty' => [
                    'id' => $load->user_id,
                    'full_name' => $load->full_name,
                    'school_id' => $load->school_id,
                    'role' => $load->role,
                    'role_label' => $this->getRoleLabel($load->role),
                ],
                'subject' => [
                    'id' => $load->subject_id,
                    'subject_code' => $load->subject_code,
                    'subject_name' => $load->subject_name,
                    'units' => $load->units,
                ],
                'program' => [
                    'id' => $load->program_id,
                    'program_name' => $load->program_name,
                ],
                'lecture_hours' => $load->lecture_hours ?? 0,
                'lab_hours' => $load->lab_hours ?? 0,
                'computed_units' => $load->computed_units ?? 0,
                'max_load_units' => $load->max_load_units,
                'created_at' => $load->created_at,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching faculty load details", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    /**
     * Helper to get role label
     */
    private function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrator',
            'department_head' => 'Department Head',
            'program_head' => 'Program Head',
            'instructor' => 'Instructor',
            'student' => 'Student',
            default => ucfirst(str_replace('_', ' ', $role)),
        };
    }

    /**
     * Show faculty load details for a specific instructor.
     */
    public function show($userId)
    {
        try {
            $instructor = User::findOrFail($userId);

            if (!$instructor->isEligibleInstructor()) {
                return back()->withErrors("This user is not an eligible instructor.");
            }

            $subjects = $this->facultyLoadService->getInstructorSubjects($userId);
            $availableSubjects = Subject::whereNotIn('id', $subjects->pluck('id'))
                                        ->orderBy('subject_name')
                                        ->get();

            return view('admin.faculty_load.show', [
                'instructor' => $instructor,
                'assignedSubjects' => $subjects,
                'availableSubjects' => $availableSubjects,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->withErrors("Instructor not found.");
        }
    }

    /**
     * Assign a subject to an instructor with teaching hours.
     */
    public function assignSubject(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'max_load_units' => 'nullable|integer|min:1',
        ]);

        // Custom validation: at least one must be greater than 0
        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Either lecture hours or laboratory hours must be greater than zero.',
                'errors' => [
                    'lecture_hours' => ['Either lecture hours or laboratory hours must be greater than zero.'],
                    'lab_hours' => ['Either lecture hours or laboratory hours must be greater than zero.'],
                ]
            ], 422);
        }

        // Custom validation: lab hours must be divisible by 3
        if ($validated['lab_hours'] > 0 && $validated['lab_hours'] % 3 !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Laboratory hours must be divisible by 3.',
                'errors' => [
                    'lab_hours' => ['Laboratory hours must be divisible by 3.'],
                ]
            ], 422);
        }

        try {
            $result = $this->facultyLoadService->assignSubjectToInstructor(
                $validated['user_id'],
                $validated['subject_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                $validated['max_load_units'] ?? null
            );

            if ($result['success']) {
                Log::info("Subject assigned", [
                    'user_id' => $validated['user_id'],
                    'subject_id' => $validated['subject_id'],
                    'lecture_hours' => $validated['lecture_hours'],
                    'lab_hours' => $validated['lab_hours'],
                    'message' => $result['message'],
                ]);
                return response()->json(['success' => true, 'message' => $result['message']]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error assigning subject", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Update teaching hours for an instructor-subject assignment.
     */
    public function updateConstraints(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'max_load_units' => 'nullable|integer|min:1',
        ]);

        // Custom validation: at least one must be greater than 0
        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Either lecture hours or laboratory hours must be greater than zero.',
                'errors' => [
                    'lecture_hours' => ['Either lecture hours or laboratory hours must be greater than zero.'],
                    'lab_hours' => ['Either lecture hours or laboratory hours must be greater than zero.'],
                ]
            ], 422);
        }

        // Custom validation: lab hours must be divisible by 3
        if ($validated['lab_hours'] > 0 && $validated['lab_hours'] % 3 !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'Laboratory hours must be divisible by 3.',
                'errors' => [
                    'lab_hours' => ['Laboratory hours must be divisible by 3.'],
                ]
            ], 422);
        }

        try {
            $result = $this->facultyLoadService->updateLoadConstraints(
                $validated['user_id'],
                $validated['subject_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                $validated['max_load_units'] ?? null
            );

            if ($result['success']) {
                Log::info("Teaching hours updated", [
                    'user_id' => $validated['user_id'],
                    'subject_id' => $validated['subject_id'],
                    'lecture_hours' => $validated['lecture_hours'],
                    'lab_hours' => $validated['lab_hours'],
                ]);
                return response()->json(['success' => true, 'message' => $result['message']]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error updating teaching hours", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Remove a subject assignment from an instructor.
     */
    public function removeAssignment(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        try {
            $result = $this->facultyLoadService->removeSubjectAssignment(
                $validated['user_id'],
                $validated['subject_id']
            );

            if ($result['success']) {
                Log::info("Subject assignment removed", [
                    'user_id' => $validated['user_id'],
                    'subject_id' => $validated['subject_id'],
                ]);
                return response()->json(['success' => true, 'message' => $result['message']]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error removing assignment", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get unassigned eligible instructors.
     * Useful for identifying instructors who need subject assignments.
     */
    public function getUnassignedInstructors()
    {
        try {
            $unassigned = $this->facultyLoadService->getUnassignedInstructors();

            return response()->json([
                'success' => true,
                'data' => $unassigned->map(fn($instructor) => [
                    'id' => $instructor->id,
                    'name' => $instructor->full_name,
                    'role' => $instructor->role,
                    'role_label' => $instructor->getRoleLabel(),
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching unassigned instructors", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get subject instructors (all instructors assigned to a specific subject).
     */
    public function getSubjectInstructors($subjectId)
    {
        try {
            $instructors = $this->facultyLoadService->getSubjectInstructors($subjectId);

            return response()->json([
                'success' => true,
                'data' => $instructors->map(fn($instructor) => [
                    'id' => $instructor->id,
                    'name' => $instructor->full_name,
                    'role' => $instructor->role,
                    'role_label' => $instructor->getRoleLabel(),
                    'lecture_hours' => $instructor->pivot->lecture_hours ?? 0,
                    'lab_hours' => $instructor->pivot->lab_hours ?? 0,
                    'computed_units' => $instructor->pivot->computed_units ?? 0,
                    'max_load_units' => $instructor->pivot->max_load_units,
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching subject instructors", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get instructor load summary with aggregated hours and units.
     */
    public function getInstructorSummary($userId)
    {
        try {
            $summary = $this->facultyLoadService->getInstructorLoadSummary($userId);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching instructor summary", ['error' => $e->getMessage(), 'user_id' => $userId]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * Get faculty load summary (statistics dashboard).
     */
    public function getSummary()
    {
        try {
            $summary = $this->facultyLoadService->getFacultyLoadSummary();

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching summary", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred.'], 500);
        }
    }
}
