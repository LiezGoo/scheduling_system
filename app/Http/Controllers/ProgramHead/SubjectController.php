<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Return filtered subjects for the Assign Faculty Load modal.
     */
    public function getFilteredSubjects(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isProgramHead()) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $department = $user->getInferredDepartment();
        if (!$department) {
            return response()->json(['message' => 'No department assigned.'], 403);
        }

        $validated = $request->validate([
            'program_id' => 'required|integer|exists:programs,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester_id' => 'required|integer|exists:semesters,id',
            'year_level' => 'required|integer|min:1|max:6',
        ]);

        Log::debug('ProgramHead subject filter request', $validated);

        $program = Program::query()->findOrFail($validated['program_id']);
        if ((int) $program->department_id !== (int) $department->id) {
            return response()->json(['message' => 'Program does not belong to your department.'], 403);
        }

        $semesterName = trim((string) Semester::query()->where('id', $validated['semester_id'])->value('name'));
        if ($semesterName === '') {
            return response()->json(['message' => 'The selected semester is invalid.'], 422);
        }

        $semesterTokens = $this->buildSemesterFilterTokens($semesterName);

        $subjects = Subject::query()
            ->select('subjects.id', 'subjects.subject_code', 'subjects.subject_name', 'subjects.lecture_hours', 'subjects.lab_hours')
            ->join('program_subjects', 'program_subjects.subject_id', '=', 'subjects.id')
            ->where('subjects.department_id', $department->id)
            ->where('subjects.is_active', true)
            ->where('program_subjects.program_id', $validated['program_id'])
            ->where('program_subjects.year_level', $validated['year_level'])
            ->where(function ($query) use ($semesterName, $semesterTokens) {
                $query->where('program_subjects.semester', $semesterName);

                if (!empty($semesterTokens)) {
                    $query->orWhereIn(DB::raw('LOWER(TRIM(program_subjects.semester))'), $semesterTokens);
                }
            })
            ->orderBy('subjects.subject_code')
            ->get()
            ->map(function ($subject) {
                $lectureHours = (int) ($subject->lecture_hours ?? 0);
                $labHours = (int) ($subject->lab_hours ?? 0);

                return [
                    'id' => (int) $subject->id,
                    'code' => (string) $subject->subject_code,
                    'name' => (string) $subject->subject_name,
                    'lecture_hours' => $lectureHours,
                    'lab_hours' => $labHours,
                    'total_hours' => $lectureHours + $labHours,
                    'block' => null,
                    'status' => 'available',
                    // Keep compatibility with existing assignment table renderer.
                    'subject_id' => (int) $subject->id,
                    'subject_code' => (string) $subject->subject_code,
                    'subject_name' => (string) $subject->subject_name,
                    'already_assigned' => false,
                    'error' => null,
                ];
            })
            ->values();

        Log::debug('ProgramHead subject filter result', [
            'program_id' => (int) $validated['program_id'],
            'academic_year_id' => (int) $validated['academic_year_id'],
            'semester_id' => (int) $validated['semester_id'],
            'semester_name' => $semesterName,
            'semester_tokens' => $semesterTokens,
            'year_level' => (int) $validated['year_level'],
            'subject_count' => $subjects->count(),
        ]);

        return response()->json([
            'success' => true,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Build semester aliases to handle mixed stored values (e.g. 1, 1st, 1st Semester).
     */
    private function buildSemesterFilterTokens(string $semesterName): array
    {
        $normalized = strtolower(trim($semesterName));
        if ($normalized === '') {
            return [];
        }

        $tokens = [$normalized];
        $withoutWord = trim(str_replace('semester', '', $normalized));
        if ($withoutWord !== '') {
            $tokens[] = $withoutWord;
        }

        $digit = null;
        if (preg_match('/\d+/', $normalized, $matches)) {
            $digit = (int) $matches[0];
        } else {
            $digit = match (true) {
                str_contains($normalized, 'first') => 1,
                str_contains($normalized, 'second') => 2,
                str_contains($normalized, 'third') => 3,
                default => null,
            };
        }

        if ($digit !== null && $digit > 0) {
            $ordinal = match ($digit) {
                1 => '1st',
                2 => '2nd',
                3 => '3rd',
                default => $digit . 'th',
            };

            $tokens[] = (string) $digit;
            $tokens[] = $ordinal;
            $tokens[] = $ordinal . ' semester';
        }

        return collect($tokens)
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Display a listing of subjects for the program head's department (READ-ONLY).
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('viewAny', Subject::class);

        // Program heads can only VIEW subjects from their department
        if (!$user->isProgramHead()) {
            abort(403, 'Unauthorized access.');
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $query = Subject::with(['department', 'creator'])
            ->forDepartment($department->id)
            ->active();

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered subjects
        $subjects = $query->orderBy('subject_code')->paginate($perPage)->appends($request->query());

        // Get department information
        $departmentName = $department->department_name;

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('program-head.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => (string) $subjects->withQueryString()->links(),
            ]);
        }

        return view('program-head.subjects.index', [
            'subjects' => $subjects,
            'departmentName' => $departmentName,
            'readOnly' => true, // Program heads have read-only access
        ]);
    }

    /**
     * Display the specified subject details (READ-ONLY).
     */
    public function show(Request $request, Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('view', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to program head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

        // Check if this is an AJAX request for JSON details
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'subject' => [
                    'id' => $subject->id,
                    'subject_code' => $subject->subject_code,
                    'subject_name' => $subject->subject_name,
                    'units' => $subject->units,
                    'lecture_hours' => $subject->lecture_hours,
                    'lab_hours' => $subject->lab_hours,
                    'description' => $subject->description,
                    'is_active' => $subject->is_active,
                    'department_name' => $subject->department->department_name,
                    'created_by' => $subject->creator->full_name ?? 'System',
                ]
            ]);
        }

        $subject->load(['department', 'creator']);
        return view('program-head.subjects.show', compact('subject'));
    }

    /**
     * Program heads CANNOT create subjects.
     */
    public function store(Request $request)
    {
        abort(403, 'Program Heads cannot create subjects. Contact your Department Head.');
    }

    /**
     * Program heads CANNOT update subjects.
     */
    public function update(Request $request, Subject $subject)
    {
        abort(403, 'Program Heads cannot edit subjects. Contact your Department Head.');
    }

    /**
     * Program heads CANNOT delete subjects.
     */
    public function destroy(Subject $subject)
    {
        abort(403, 'Program Heads cannot delete subjects. Contact your Department Head.');
    }
}
