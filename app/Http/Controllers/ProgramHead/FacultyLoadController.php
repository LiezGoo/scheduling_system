<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Block;
use App\Models\Department;
use App\Models\Program;
use App\Models\Semester;
use App\Models\User;
use App\Models\Subject;
use App\Models\FacultyWorkloadConfiguration;
use App\Models\YearLevel;
use App\Services\FacultyLoadService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

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

        // Build instructor loads query - GROUP BY instructor to aggregate subjects
        // Database-agnostic GROUP_CONCAT for SQLite and MySQL
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: GROUP_CONCAT without DISTINCT to avoid syntax errors
            // GROUP BY prevents most duplicates; default separator is comma
            $subjectCodesConcat = "GROUP_CONCAT(subjects.subject_code)";
            $subjectNamesConcat = "GROUP_CONCAT(subjects.subject_name)";
        } else {
            // MySQL: use DISTINCT and explicit separator
            $subjectCodesConcat = "GROUP_CONCAT(DISTINCT subjects.subject_code)";
            $subjectNamesConcat = "GROUP_CONCAT(DISTINCT subjects.subject_name SEPARATOR ', ')";
        }
        
        $baseQuery = DB::table('instructor_loads')
            ->join('users', 'instructor_loads.instructor_id', '=', 'users.id')
            ->join('subjects', 'instructor_loads.subject_id', '=', 'subjects.id')
            ->join('programs', 'instructor_loads.program_id', '=', 'programs.id')
            ->join('academic_years', 'instructor_loads.academic_year_id', '=', 'academic_years.id')
            ->join('departments', 'subjects.department_id', '=', 'departments.id')
            ->where('subjects.department_id', $department->id)
            ->select(
                'instructor_loads.instructor_id',
                'users.school_id',
                'users.role',
                'users.contract_type',
                'users.first_name',
                'users.last_name',
                DB::raw("trim(users.first_name || ' ' || users.last_name) as full_name"),
                'programs.program_name',
                'academic_years.name as academic_year_name',
                'departments.id as department_id',
                'departments.department_name',
                'instructor_loads.program_id',
                'instructor_loads.academic_year_id',
                'instructor_loads.semester',
                'instructor_loads.year_level',
                DB::raw($subjectCodesConcat . ' as subject_codes'),
                DB::raw($subjectNamesConcat . ' as subject_names'),
                DB::raw('COUNT(DISTINCT instructor_loads.id) as total_subjects'),
                DB::raw('SUM(instructor_loads.lec_hours) as total_lec_hours'),
                DB::raw('SUM(instructor_loads.lab_hours) as total_lab_hours'),
                DB::raw('SUM(instructor_loads.total_hours) as total_teaching_hours'),
                DB::raw('MIN(instructor_loads.id) as load_id')
            );

        $query = $baseQuery->groupBy(
            'instructor_loads.instructor_id',
            'users.school_id',
            'users.role',
            'users.contract_type',
            'users.first_name',
            'users.last_name',
            'programs.program_name',
            'academic_years.name',
            'departments.id',
            'departments.department_name',
            'instructor_loads.program_id',
            'instructor_loads.academic_year_id',
            'instructor_loads.semester',
            'instructor_loads.year_level'
        );

        // Apply filters
        if ($request->filled('faculty')) {
            $query->where('users.id', $request->faculty);
        }

        if ($request->filled('role')) {
            $query->where('users.role', $request->role);
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

        // Subject filter needs HAVING clause for grouped queries
        if ($request->filled('subject')) {
            $subjectId = $request->subject;
            $query->whereIn('instructor_loads.id', function ($subQuery) use ($subjectId) {
                $subQuery->select('id')
                    ->from('instructor_loads')
                    ->where('subject_id', $subjectId);
            });
        }

        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $facultyLoads = $query->orderBy('users.first_name')
            ->orderBy('users.last_name')
            ->paginate($perPage)
            ->appends($request->query());

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

        $semesterOptions = $this->resolveDepartmentSemesterOptions($department->id);
        $semesters = Semester::query()->orderBy('name')->get();
        $yearLevelOptions = $this->resolveDepartmentYearLevelOptions($department->id);

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
            'semesterOptions' => $semesterOptions,
            'semesters' => $semesters,
            'yearLevelOptions' => $yearLevelOptions,
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

        $yearLevelOptions = $this->resolveDepartmentYearLevelOptions($department->id);

        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'faculty_id' => 'required_without:user_id|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
            'subject_id' => 'required_without:subjects|nullable|integer|exists:subjects,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'year_level' => ['required', 'integer', Rule::in($yearLevelOptions->all())],
            'block_id' => 'required|integer|exists:blocks,id',
            'block_section' => 'nullable|string|max:20',
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
        $semesterName = trim((string) Semester::query()->where('id', $validated['semester_id'])->value('name'));
        $block = Block::query()->find($validated['block_id']);
        $blockSectionName = trim((string) ($block?->block_name ?? ''));

        if ($semesterName === '') {
            return response()->json([
                'success' => false,
                'message' => 'The selected semester is invalid.'
            ], 422);
        }

        if ($blockSectionName === '') {
            return response()->json([
                'success' => false,
                'message' => 'The selected block/section is invalid.'
            ], 422);
        }

        $program = Program::findOrFail($validated['program_id']);
        if ($program->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Program does not belong to your department.'
            ], 403);
        }

        $forceAssign = (bool) ($validated['force_assign'] ?? false);

        $workloadConfig = FacultyWorkloadConfiguration::query()
            ->where('user_id', $userId)
            ->where('program_id', $validated['program_id'])
            ->where('is_active', true)
            ->first();

        if (!$workloadConfig || empty($workloadConfig->teaching_scheme)) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty teaching scheme is not configured. Please configure faculty workload availability first.',
            ], 422);
        }

        $subjectRows = $validated['subjects'] ?? [];

        if (!empty($subjectRows)) {
            $subjectRows = collect($subjectRows)
                ->map(function (array $subjectRow) use ($blockSectionName) {
                    $subjectRow['block'] = $blockSectionName;
                    return $subjectRow;
                })
                ->values()
                ->all();
        }

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
                $semesterName,
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
                $semesterName,
                $validated['year_level'],
                $blockSectionName,
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

        $yearLevelOptions = $this->resolveDepartmentYearLevelOptions($department->id);

        $validated = $request->validate([
            'faculty_id' => 'required|integer|exists:users,id',
            'program_id' => 'required|integer|exists:programs,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'year_level' => ['required', 'integer', Rule::in($yearLevelOptions->all())],
            'block_id' => 'required|integer|exists:blocks,id',
            'block_section' => 'nullable|string|max:20',
        ]);

        $semesterName = trim((string) Semester::query()->where('id', $validated['semester_id'])->value('name'));

        if ($semesterName === '') {
            return response()->json([
                'success' => false,
                'message' => 'The selected semester is invalid.',
            ], 422);
        }

        $block = Block::query()->find($validated['block_id']);
        $blockSection = trim((string) ($block?->block_name ?? ''));
        if ($blockSection === '') {
            return response()->json([
                'success' => false,
                'message' => 'The selected block/section is invalid.',
            ], 422);
        }

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
            ->where('program_subjects.semester', $semesterName)
            ->orderBy('subjects.subject_code')
            ->get();

        $currentLoad = $faculty->getInstructorLoadSummaryForTerm(
            $validated['academic_year_id'],
            $semesterName
        );

        $workloadConfig = FacultyWorkloadConfiguration::query()
            ->where('user_id', $faculty->id)
            ->when(!empty($validated['program_id']), fn ($query) => $query->where('program_id', $validated['program_id']))
            ->where('is_active', true)
            ->first();

        if (!$workloadConfig) {
            $workloadConfig = FacultyWorkloadConfiguration::query()
                ->where('user_id', $faculty->id)
                ->where('is_active', true)
                ->first();
        }

        $contractLimits = $faculty->getContractLoadLimits();
        $lectureLimit = $workloadConfig->max_lecture_hours_per_week ?? ($contractLimits['max_lecture_hours'] ?? null);
        $labLimit = $workloadConfig->max_lab_hours_per_week ?? ($contractLimits['max_lab_hours'] ?? null);

        $subjectIds = $subjects->pluck('id')->all();

        $existingAssignments = DB::table('instructor_loads')
            ->where('instructor_id', $validated['faculty_id'])
            ->where('program_id', $validated['program_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $semesterName)
            ->where('year_level', $validated['year_level'])
            ->whereIn('subject_id', $subjectIds)
            ->where('block_section', $blockSection)
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
                'contract_type' => $contractLimits['type'] ?? 'unspecified',
                'max_lecture_hours' => $lectureLimit,
                'max_lab_hours' => $labLimit,
            ],
        ]);
    }

    /**
     * Fetch subjects for assign faculty load modal once all required filters are selected.
     */
    public function fetchSubjects(Request $request)
    {
        $user = Auth::user();
        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        // Reuse existing validated payload and response structure.
        return $this->getAssignableSubjects($request);
    }

    /**
     * Filter blocks by selected assignment context.
     */
    public function filterBlocks(Request $request)
    {
        $user = Auth::user();
        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'program_id' => 'required|integer|exists:programs,id',
            'year_level_id' => 'required|integer',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
        ]);

        $program = Program::query()->findOrFail($validated['program_id']);
        if ((int) $program->department_id !== (int) $department->id) {
            return response()->json([], 403);
        }

        // Year level select may provide either a YearLevel ID or numeric code (e.g. 1, 2, 3).
        $yearLevelToken = (string) $validated['year_level_id'];
        $yearLevelIds = YearLevel::query()
            ->where('id', (int) $validated['year_level_id'])
            ->orWhere('code', $yearLevelToken)
            ->pluck('id')
            ->unique()
            ->values();

        if ($yearLevelIds->isEmpty()) {
            return response()->json([]);
        }

        $blocks = Block::query()
            ->where('status', Block::STATUS_ACTIVE)
            ->where('program_id', $validated['program_id'])
            ->whereIn('year_level_id', $yearLevelIds->all())
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester_id', $validated['semester_id'])
            ->orderBy('block_name')
            ->get(['id', 'block_name']);

        return response()->json($blocks);
    }

    /**
     * Get faculty workload limits and current load for summary panel.
     */
    public function getFacultyWorkload(Request $request, int $facultyId)
    {
        $user = Auth::user();
        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'program_id' => 'nullable|integer|exists:programs,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'semester_id' => 'nullable|integer|exists:semesters,id',
        ]);

        $semesterName = null;
        if (!empty($validated['semester_id'])) {
            $semesterName = trim((string) Semester::query()->where('id', $validated['semester_id'])->value('name'));
        }

        $faculty = User::findOrFail($facultyId);

        $workloadConfig = FacultyWorkloadConfiguration::query()
            ->where('user_id', $facultyId)
            ->when(!empty($validated['program_id']), fn ($query) => $query->where('program_id', $validated['program_id']))
            ->where('is_active', true)
            ->first();

        if (!$workloadConfig) {
            $workloadConfig = FacultyWorkloadConfiguration::query()
                ->where('user_id', $facultyId)
                ->where('is_active', true)
                ->first();
        }

        $contractLimits = $faculty->getContractLoadLimits();
        $lectureLimit = $workloadConfig->max_lecture_hours_per_week ?? ($contractLimits['max_lecture_hours'] ?? 0);
        $labLimit = $workloadConfig->max_lab_hours_per_week ?? ($contractLimits['max_lab_hours'] ?? 0);

        $currentLecture = 0;
        $currentLab = 0;

        if (!empty($validated['academic_year_id']) && !empty($semesterName)) {
            $summary = $faculty->getInstructorLoadSummaryForTerm(
                $validated['academic_year_id'],
                $semesterName
            );
            $currentLecture = (int) ($summary['total_lecture_hours'] ?? 0);
            $currentLab = (int) ($summary['total_lab_hours'] ?? 0);
        }

        return response()->json([
            'success' => true,
            'lecture_limit' => $lectureLimit,
            'lab_limit' => $labLimit,
            'current_lecture' => $currentLecture,
            'current_lab' => $currentLab,
            'max_hours_per_day' => $workloadConfig->max_hours_per_day ?? null,
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
            $workloadValidation = $instructor?->validateFacultyLoad(0, 0, (int) $load->academic_year_id, (string) $load->semester) ?? [];

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
                'workload' => [
                    'status' => $workloadValidation['workload_status'] ?? 'Normal',
                    'total_assigned_hours' => (int) ($workloadValidation['total_assigned_hours'] ?? ($summary['total_assigned_hours'] ?? 0)),
                    'max_load' => $workloadValidation['max_load'] ?? null,
                    'overload_hours' => (int) ($workloadValidation['overload_hours'] ?? 0),
                ],
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
     * Get faculty load summary with selected subjects for dynamic validation.
     */
    public function getFacultyLoadSummary(Request $request)
    {
        $user = Auth::user();
        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'faculty_id' => 'required|integer|exists:users,id',
            'program_id' => 'nullable|integer|exists:programs,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'semester_id' => 'nullable|integer|exists:semesters,id',
            'selected_lecture_hours' => 'nullable|integer|min:0',
            'selected_lab_hours' => 'nullable|integer|min:0',
        ]);

        $semesterName = null;
        if (!empty($validated['semester_id'])) {
            $semesterName = trim((string) Semester::query()->where('id', $validated['semester_id'])->value('name'));
        }

        $facultyId = $validated['faculty_id'];
        $selectedLecture = (int) ($validated['selected_lecture_hours'] ?? 0);
        $selectedLab = (int) ($validated['selected_lab_hours'] ?? 0);

        $faculty = User::findOrFail($facultyId);
        
        // Get faculty workload configuration
        $workloadConfig = null;
        if (!empty($validated['program_id'])) {
            $workloadConfig = FacultyWorkloadConfiguration::where('user_id', $facultyId)
                ->where('program_id', $validated['program_id'])
                ->where('is_active', true)
                ->first();
        }

        // If no program-specific config, try to get any active config
        if (!$workloadConfig) {
            $workloadConfig = FacultyWorkloadConfiguration::where('user_id', $facultyId)
                ->where('is_active', true)
                ->first();
        }

        // Calculate current load for the term
        $currentLecture = 0;
        $currentLab = 0;
        
        if (!empty($validated['academic_year_id']) && !empty($semesterName)) {
            $loadSummary = $faculty->getInstructorLoadSummaryForTerm(
                $validated['academic_year_id'],
                $semesterName
            );
            $currentLecture = (int) ($loadSummary['total_lecture_hours'] ?? 0);
            $currentLab = (int) ($loadSummary['total_lab_hours'] ?? 0);
        }

        // Get limits from workload config or contract defaults
        $lectureLimit = $workloadConfig->max_lecture_hours_per_week ?? null;
        $labLimit = $workloadConfig->max_lab_hours_per_week ?? null;

        // If no workload config, fall back to contract limits
        if ($lectureLimit === null || $labLimit === null) {
            $contractLimits = $faculty->getContractLoadLimits();
            $lectureLimit = $lectureLimit ?? $contractLimits['max_lecture_hours'] ?? null;
            $labLimit = $labLimit ?? $contractLimits['max_lab_hours'] ?? null;
        }

        // Calculate projected values
        $projectedLecture = $currentLecture + $selectedLecture;
        $projectedLab = $currentLab + $selectedLab;
        
        $remainingLecture = $lectureLimit !== null ? ($lectureLimit - $projectedLecture) : null;
        $remainingLab = $labLimit !== null ? ($labLimit - $projectedLab) : null;

        // Determine status with color coding
        $lectureStatus = 'valid';
        $labStatus = 'valid';

        if ($lectureLimit !== null) {
            $lectureRatio = $lectureLimit > 0 ? ($projectedLecture / $lectureLimit) : 0;
            if ($projectedLecture > $lectureLimit) {
                $lectureStatus = 'exceeded';
            } elseif ($lectureRatio >= 0.85) {
                $lectureStatus = 'approaching';
            }
        }

        if ($labLimit !== null) {
            $labRatio = $labLimit > 0 ? ($projectedLab / $labLimit) : 0;
            if ($projectedLab > $labLimit) {
                $labStatus = 'exceeded';
            } elseif ($labRatio >= 0.85) {
                $labStatus = 'approaching';
            }
        }

        return response()->json([
            'success' => true,
            'current_load' => [
                'lecture_hours' => $currentLecture,
                'lab_hours' => $currentLab,
            ],
            'limits' => [
                'lecture_limit' => $lectureLimit,
                'lab_limit' => $labLimit,
            ],
            'projected_load' => [
                'lecture_hours' => $projectedLecture,
                'lab_hours' => $projectedLab,
            ],
            'remaining_load' => [
                'lecture_hours' => $remainingLecture,
                'lab_hours' => $remainingLab,
            ],
            'status' => [
                'lecture' => $lectureStatus,
                'lab' => $labStatus,
                'can_assign' => $lectureStatus !== 'exceeded' && $labStatus !== 'exceeded',
            ],
            'has_workload_config' => $workloadConfig !== null,
        ]);
    }

    /**
     * Resolve valid semester values for a department.
     * Prefer active semester records, with curriculum fallback when empty.
     */
    private function resolveDepartmentSemesterOptions(int $departmentId): Collection
    {
        $semesterOptions = Semester::query()
            ->where('status', Semester::STATUS_ACTIVE)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($semesterOptions->isEmpty()) {
            $semesterOptions = DB::table('program_subjects')
                ->join('programs', 'programs.id', '=', 'program_subjects.program_id')
                ->where('programs.department_id', $departmentId)
                ->whereNotNull('program_subjects.semester')
                ->distinct()
                ->pluck('program_subjects.semester')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->values();
        }

        return $semesterOptions;
    }

    /**
     * Resolve valid numeric year level values for a department,
     * preferring active year_levels and falling back to curriculum data.
     */
    private function resolveDepartmentYearLevelOptions(int $departmentId): Collection
    {
        $yearLevelOptions = YearLevel::query()
            ->where('status', YearLevel::STATUS_ACTIVE)
            ->orderByRaw('CAST(COALESCE(NULLIF(code, \'\'), id) AS UNSIGNED)')
            ->get()
            ->map(function (YearLevel $yearLevel) {
                $value = $yearLevel->code !== null && trim((string) $yearLevel->code) !== ''
                    ? trim((string) $yearLevel->code)
                    : (string) $yearLevel->id;

                return ctype_digit($value) ? (int) $value : null;
            })
            ->filter(fn ($value) => is_int($value) && $value > 0)
            ->unique()
            ->sort()
            ->values();

        if ($yearLevelOptions->isEmpty()) {
            $yearLevelOptions = DB::table('program_subjects')
                ->join('programs', 'programs.id', '=', 'program_subjects.program_id')
                ->where('programs.department_id', $departmentId)
                ->whereNotNull('program_subjects.year_level')
                ->distinct()
                ->pluck('program_subjects.year_level')
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->sort()
                ->values();
        }

        return $yearLevelOptions;
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
