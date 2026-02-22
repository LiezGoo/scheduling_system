<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\User;
use App\Models\Subject;
use App\Models\Department;
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
        // Build faculty loads query (flattened view of all instructor load assignments)
        $query = DB::table('instructor_loads')
            ->join('users', 'instructor_loads.instructor_id', '=', 'users.id')
            ->join('subjects', 'instructor_loads.subject_id', '=', 'subjects.id')
            ->join('programs', 'instructor_loads.program_id', '=', 'programs.id')
            ->join('academic_years', 'instructor_loads.academic_year_id', '=', 'academic_years.id')
            ->join('departments', 'subjects.department_id', '=', 'departments.id')
                ->select(
                    'instructor_loads.*',
                    DB::raw("trim(users.first_name || ' ' || users.last_name) as full_name"),
                    'users.school_id',
                    'users.role',
                    'users.contract_type',
                    'subjects.subject_code',
                    'subjects.subject_name',
                    'subjects.units',
                    'programs.program_name',
                    'programs.id as program_id',
                    'academic_years.name as academic_year_name',
                    'departments.department_name',
                    'departments.id as department_id'
                )
            ->where('users.status', 'active')
            ->whereIn('users.role', [User::ROLE_INSTRUCTOR, User::ROLE_PROGRAM_HEAD, User::ROLE_DEPARTMENT_HEAD]);

        // Filter by faculty name or ID
        if ($request->filled('faculty')) {
            $search = '%' . $request->input('faculty') . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->whereRaw("trim(users.first_name || ' ' || users.last_name) LIKE ?", [$search])
                         ->orWhere('users.school_id', 'LIKE', $search);
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('departments.id', $request->input('department'));
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('users.role', $request->input('role'));
        }

        // Filter by subject
        if ($request->filled('subject')) {
            $query->where('subjects.id', $request->input('subject'));
        }

        if ($request->filled('program')) {
            $query->where('programs.id', $request->input('program'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_years.id', $request->input('academic_year_id'));
        }

        if ($request->filled('semester')) {
            $query->where('instructor_loads.semester', $request->input('semester'));
        }

        // Get per page value
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Paginate faculty loads
        $facultyLoads = $query->orderBy('users.first_name')
                      ->orderBy('users.last_name')
                              ->paginate($perPage)
                              ->appends($request->query());

        // Get departments for filter dropdown
        $departments = Department::orderBy('department_name')->get();

        // Get subjects for filter dropdown
        $subjects = Subject::orderBy('subject_code')->get();

        // Get eligible faculty for assignment modal
        $eligibleFaculty = User::eligibleInstructors()->active()
                                ->orderBy('first_name')
                                ->orderBy('last_name')
                                ->get();

        $programs = Program::orderBy('program_name')->get();

        $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();

        // Get summary statistics
        $summary = $this->facultyLoadService->getFacultyLoadSummary();

        return view('admin.faculty_load.index', [
            'facultyLoads' => $facultyLoads,
            'departments' => $departments,
            'subjects' => $subjects,
            'programs' => $programs,
            'academicYears' => $academicYears,
            'eligibleFaculty' => $eligibleFaculty,
            'summary' => $summary,
            'currentFilters' => [
                'faculty' => $request->input('faculty'),
                'department' => $request->input('department'),
                'role' => $request->input('role'),
                'subject' => $request->input('subject'),
                'program' => $request->input('program'),
                'academic_year_id' => $request->input('academic_year_id'),
                'semester' => $request->input('semester'),
            ],
        ]);
    }

    /**
     * Get faculty load details as JSON (for modals).
     */
    public function getDetails($facultyLoadId)
    {
        try {
            $load = DB::table('instructor_loads')
                       ->join('users', 'instructor_loads.instructor_id', '=', 'users.id')
                       ->join('subjects', 'instructor_loads.subject_id', '=', 'subjects.id')
                       ->join('programs', 'instructor_loads.program_id', '=', 'programs.id')
                       ->join('academic_years', 'instructor_loads.academic_year_id', '=', 'academic_years.id')
                       ->join('departments', 'subjects.department_id', '=', 'departments.id')
                       ->select(
                           'instructor_loads.*',
                           'users.id as user_id',
                           DB::raw("trim(users.first_name || ' ' || users.last_name) as full_name"),
                           'users.school_id',
                           'users.role',
                           'users.contract_type',
                           'subjects.id as subject_id',
                           'subjects.subject_code',
                           'subjects.subject_name',
                           'subjects.units',
                           'programs.id as program_id',
                           'programs.program_name',
                           'academic_years.id as academic_year_id',
                           'academic_years.name as academic_year_name',
                           'departments.id as department_id',
                           'departments.department_name'
                       )
                       ->where('instructor_loads.id', $facultyLoadId)
                       ->first();

            if (!$load) {
                return response()->json(['success' => false, 'message' => 'Faculty load not found'], 404);
            }

            $instructor = User::find($load->user_id);
            $limits = $instructor?->getContractLoadLimits() ?? [];
            $summary = $instructor?->getInstructorLoadSummaryForTerm($load->academic_year_id, $load->semester) ?? [];

            return response()->json([
                'success' => true,
                'id' => $load->id,
                'faculty' => [
                    'id' => $load->user_id,
                    'full_name' => $load->full_name,
                    'school_id' => $load->school_id,
                    'role' => $load->role,
                    'role_label' => $this->getRoleLabel($load->role),
                    'contract_type' => $load->contract_type,
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
                'academic_year' => [
                    'id' => $load->academic_year_id,
                    'name' => $load->academic_year_name,
                ],
                'department' => [
                    'id' => $load->department_id,
                    'department_name' => $load->department_name,
                ],
                'lecture_hours' => $load->lec_hours ?? 0,
                'lab_hours' => $load->lab_hours ?? 0,
                'total_hours' => $load->total_hours ?? 0,
                'semester' => $load->semester,
                'year_level' => $load->year_level,
                'block_section' => $load->block_section,
                'limits' => $limits,
                'current_load' => $summary,
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
            'user_id' => 'nullable|integer|exists:users,id',
            'faculty_id' => 'required_without:user_id|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester' => 'required|string',
            'year_level' => 'required|integer|min:1|max:6',
            'block_section' => 'required|string|max:20',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'force_assign' => 'nullable|boolean',
        ]);

        $userId = $validated['user_id'] ?? $validated['faculty_id'];

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
                $userId,
                $validated['subject_id'],
                $validated['program_id'],
                $validated['academic_year_id'],
                $validated['semester'],
                $validated['year_level'],
                $validated['block_section'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                (bool) ($validated['force_assign'] ?? false)
            );

            if ($result['success']) {
                Log::info("Subject assigned", [
                    'user_id' => $userId,
                    'subject_id' => $validated['subject_id'],
                    'lecture_hours' => $validated['lecture_hours'],
                    'lab_hours' => $validated['lab_hours'],
                    'message' => $result['message'],
                ]);
                return response()->json(['success' => true, 'message' => $result['message']]);
            }

            if (($result['code'] ?? '') === 'overload') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'validation_details' => $result['validation_details'] ?? [],
                ], 409);
            }

            return response()->json(['success' => false, 'message' => $result['message']], 422);
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
            'faculty_load_id' => 'required|integer|exists:instructor_loads,id',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'force_assign' => 'nullable|boolean',
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
                $validated['faculty_load_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                (bool) ($validated['force_assign'] ?? false)
            );

            if ($result['success']) {
                Log::info("Teaching hours updated", [
                    'faculty_load_id' => $validated['faculty_load_id'],
                    'lecture_hours' => $validated['lecture_hours'],
                    'lab_hours' => $validated['lab_hours'],
                ]);
                return response()->json(['success' => true, 'message' => $result['message']]);
            }

            if (($result['code'] ?? '') === 'overload') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'validation_details' => $result['validation_details'] ?? [],
                ], 409);
            }

            return response()->json(['success' => false, 'message' => $result['message']], 422);
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
            'faculty_load_id' => 'required|integer|exists:instructor_loads,id',
        ]);

        try {
            $result = $this->facultyLoadService->removeSubjectAssignment(
                $validated['faculty_load_id']
            );

            if ($result['success']) {
                Log::info("Subject assignment removed", [
                    'faculty_load_id' => $validated['faculty_load_id'],
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
                'data' => $instructors->map(fn($load) => [
                    'id' => $load->instructor_id,
                    'name' => $load->instructor?->full_name,
                    'role' => $load->instructor?->role,
                    'role_label' => $load->instructor?->getRoleLabel(),
                    'lecture_hours' => $load->lec_hours ?? 0,
                    'lab_hours' => $load->lab_hours ?? 0,
                    'total_hours' => $load->total_hours ?? 0,
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
