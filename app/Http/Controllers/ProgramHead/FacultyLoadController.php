<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Program;
use App\Models\User;
use App\Models\Subject;
use App\Services\FacultyLoadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacultyLoadController extends Controller
{
    protected FacultyLoadService $facultyLoadService;

    public function __construct(FacultyLoadService $facultyLoadService)
    {
        $this->facultyLoadService = $facultyLoadService;
    }

    /**
     * Display Faculty Load Management for program head's program.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        // Build instructor loads query - scoped to program head's department
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
                'academic_years.name as academic_year_name',
                'departments.id as department_id',
                'departments.department_name'
            )
            ->where('subjects.department_id', $department->id);

        // Apply filters
        if ($request->filled('faculty')) {
            $query->where('users.id', $request->faculty);
        }

        if ($request->filled('role')) {
            $query->where('users.role', $request->role);
        }

        if ($request->filled('subject')) {
            $query->where('subjects.id', $request->subject);
        }

        if ($request->filled('department')) {
            $query->where('departments.id', $request->department);
        }

        if ($request->filled('program')) {
            $query->where('programs.id', $request->program);
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_years.id', $request->academic_year_id);
        }

        if ($request->filled('semester')) {
            $query->where('instructor_loads.semester', $request->semester);
        }

        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $facultyLoads = $query->orderBy('users.first_name')->orderBy('users.last_name')->paginate($perPage)->appends($request->query());

        $departments = Department::where('id', $department->id)
            ->orderBy('department_name')
            ->get();

        // Get subjects only from program head's department
        $subjects = Subject::where('department_id', $department->id)
            ->orderBy('subject_code')
            ->get();

        $programs = Program::where('department_id', $department->id)
            ->orderBy('program_name')
            ->get();

        $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();

        // Get eligible faculty
        $eligibleFaculty = User::eligibleInstructors()->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Get summary statistics
        $summary = $this->facultyLoadService->getFacultyLoadSummary();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('program-head.faculty-load.partials.table-rows', compact('facultyLoads'))->render(),
                'pagination' => $facultyLoads->withQueryString()->links(),
            ]);
        }

        return view('program-head.faculty-load.index', [
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
     * Assign a subject to an instructor.
     */
    public function assignSubject(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'faculty_id' => 'required_without:user_id|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
            'subject_id' => 'required_without:subjects|nullable|integer|exists:subjects,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester' => 'required|string',
            'year_level' => 'required|integer|min:1|max:6',
            'block_section' => 'required_without:subjects|nullable|string|max:20',
            'lecture_hours' => 'required_without:subjects|nullable|integer|min:0|max:40',
            'lab_hours' => 'required_without:subjects|nullable|integer|min:0|max:40',
            'subjects' => 'nullable|array|min:1',
            'subjects.*.subject_id' => 'required_with:subjects|integer|exists:subjects,id',
            'subjects.*.block' => 'nullable|string|max:20',
            'subjects.*.block_section' => 'nullable|string|max:20',
            'subjects.*.lecture_hours' => 'required_with:subjects|integer|min:0|max:40',
            'subjects.*.lab_hours' => 'required_with:subjects|integer|min:0|max:40',
            'force_assign' => 'nullable|boolean',
        ]);

        $userId = $validated['user_id'] ?? $validated['faculty_id'];

        $program = Program::findOrFail($validated['program_id']);
        if ($program->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Program does not belong to your department.'
            ], 403);
        }

        $forceAssign = (bool) ($validated['force_assign'] ?? false);

        $subjectRows = $validated['subjects'] ?? [];

        if (!empty($subjectRows)) {
            foreach ($subjectRows as $index => $subjectRow) {
                $subject = Subject::find($subjectRow['subject_id']);
                if (!$subject || (int) $subject->department_id !== (int) $department->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'One or more selected subjects do not belong to your department.',
                        'errors' => [
                            $index => [
                                'subject_id' => 'Subject does not belong to your department.',
                            ],
                        ],
                    ], 403);
                }
            }

            $bulkResult = $this->facultyLoadService->assignMultipleSubjectsToInstructor(
                $userId,
                $validated['program_id'],
                $validated['academic_year_id'],
                $validated['semester'],
                $validated['year_level'],
                $subjectRows,
                $forceAssign
            );

            if ($bulkResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $bulkResult['message'],
                    'assigned_count' => $bulkResult['assigned_count'] ?? count($subjectRows),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $bulkResult['message'] ?? 'Bulk assignment failed.',
                'errors' => $bulkResult['errors'] ?? [],
            ], 422);
        }

        // Backward-compatible single subject assignment
        $subject = Subject::findOrFail($validated['subject_id']);
        if ($subject->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Subject does not belong to your department.'
            ], 403);
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
                $forceAssign
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }

            if (($result['code'] ?? '') === 'overload') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'validation_details' => $result['validation_details'] ?? [],
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error assigning subject", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }

    /**
     * Get assignable subjects and load summary for bulk assignment modal.
     */
    public function getAssignableSubjects(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'faculty_id' => 'required|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester' => 'required|string',
            'year_level' => 'required|integer|min:1|max:6',
            'block_section' => 'nullable|string|max:20',
        ]);

        $program = Program::findOrFail($validated['program_id']);
        if ((int) $program->department_id !== (int) $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Program does not belong to your department.',
            ], 403);
        }

        $faculty = User::findOrFail($validated['faculty_id']);
        if (!$faculty->isEligibleInstructor()) {
            return response()->json([
                'success' => false,
                'message' => 'Selected faculty is not eligible for teaching assignments.',
            ], 422);
        }

        $subjects = Subject::query()
            ->select('subjects.id', 'subjects.subject_code', 'subjects.subject_name', 'subjects.lecture_hours', 'subjects.lab_hours')
            ->join('program_subjects', 'program_subjects.subject_id', '=', 'subjects.id')
            ->where('subjects.department_id', $department->id)
            ->where('subjects.is_active', true)
            ->where('program_subjects.program_id', $validated['program_id'])
            ->where('program_subjects.year_level', $validated['year_level'])
            ->where('program_subjects.semester', $validated['semester'])
            ->orderBy('subjects.subject_code')
            ->get();

        $currentLoad = $faculty->getInstructorLoadSummaryForTerm(
            $validated['academic_year_id'],
            $validated['semester']
        );

        $limits = $faculty->getContractLoadLimits();

        $blockSection = trim((string) ($validated['block_section'] ?? ''));
        $subjectIds = $subjects->pluck('id')->all();

        $existingAssignments = DB::table('instructor_loads')
            ->where('instructor_id', $validated['faculty_id'])
            ->where('program_id', $validated['program_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('year_level', $validated['year_level'])
            ->whereIn('subject_id', $subjectIds)
            ->when($blockSection !== '', fn ($query) => $query->where('block_section', $blockSection))
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rows = $subjects->map(function ($subject) use ($existingAssignments) {
            $lecture = (int) $subject->lecture_hours;
            $lab = (int) $subject->lab_hours;
            $subjectId = (int) $subject->id;
            $alreadyAssigned = in_array($subjectId, $existingAssignments, true);

            return [
                'subject_id' => $subjectId,
                'subject_code' => $subject->subject_code,
                'subject_name' => $subject->subject_name,
                'lecture_hours' => $lecture,
                'lab_hours' => $lab,
                'total_hours' => $lecture + $lab,
                'already_assigned' => $alreadyAssigned,
                'error' => $alreadyAssigned ? 'Already assigned for selected term/block.' : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'subjects' => $rows,
            'load_summary' => [
                'current_lecture_hours' => (int) ($currentLoad['total_lecture_hours'] ?? 0),
                'current_lab_hours' => (int) ($currentLoad['total_lab_hours'] ?? 0),
                'contract_type' => $limits['type'] ?? 'unspecified',
                'max_lecture_hours' => $limits['max_lecture_hours'],
                'max_lab_hours' => $limits['max_lab_hours'],
            ],
        ]);
    }

    /**
     * Get faculty load details.
     */
    public function getDetails($facultyLoadId)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

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
                ->where('subjects.department_id', $department->id)
                ->first();

            if (!$load) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty load not found or unauthorized.'
                ], 404);
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
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching faculty load details", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

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
     * Update faculty load constraints.
     */
    public function updateConstraints(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'faculty_load_id' => 'required|integer|exists:instructor_loads,id',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'force_assign' => 'nullable|boolean',
        ]);

        try {
            // Verify the faculty load belongs to program head's department
            $load = DB::table('instructor_loads')
                ->join('subjects', 'instructor_loads.subject_id', '=', 'subjects.id')
                ->where('instructor_loads.id', $validated['faculty_load_id'])
                ->where('subjects.department_id', $department->id)
                ->select('instructor_loads.id')
                ->first();

            if (!$load) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $result = $this->facultyLoadService->updateLoadConstraints(
                $validated['faculty_load_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                (bool) ($validated['force_assign'] ?? false)
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            }

            if (($result['code'] ?? '') === 'overload') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'validation_details' => $result['validation_details'] ?? [],
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error updating constraints", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }

    /**
     * Remove subject assignment.
     */
    public function removeAssignment(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'faculty_load_id' => 'required|integer|exists:instructor_loads,id',
        ]);

        $load = DB::table('instructor_loads')
            ->join('subjects', 'instructor_loads.subject_id', '=', 'subjects.id')
            ->where('instructor_loads.id', $validated['faculty_load_id'])
            ->where('subjects.department_id', $department->id)
            ->select('instructor_loads.id')
            ->first();

        if (!$load) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        try {
            $result = $this->facultyLoadService->removeSubjectAssignment(
                $validated['faculty_load_id']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error removing assignment", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }
}
