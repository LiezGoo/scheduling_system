<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\YearLevel;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    /**
     * Show the curriculum management page for program head's program.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $programs = Program::where('id', $user->program_id)
            ->orderBy('program_name')
            ->get();

        $selectedProgramId = $user->program_id;

        // Get the program head's program with subjects
        $program = Program::with(['subjects' => function ($query) {
            $query->withPivot(['year_level', 'semester']);
        }])->findOrFail($selectedProgramId);

        // Get all subjects for this department (for assignment)
        $departmentId = $program->department_id;
        $subjects = Subject::where('department_id', $departmentId)
            ->orderBy('subject_code')
            ->get();

        // Group curriculum by year and semester
        $groupedCurriculum = $program->subjects
            ->sortBy(fn ($subject) => sprintf('%02d-%s-%s', $subject->pivot->year_level, strtolower(trim((string) $subject->pivot->semester)), $subject->subject_code))
            ->groupBy(fn ($subject) => $subject->pivot->year_level)
            ->map(fn ($byYear) => $byYear->groupBy(fn ($subject) => strtolower(trim((string) $subject->pivot->semester))));

        $assignedMatrix = $program->subjects
            ->mapToGroups(function ($subject) {
                return [$subject->id => ["{$subject->pivot->year_level}|{$subject->pivot->semester}"]];
            })
            ->map(fn ($group) => collect($group)->unique()->values())
            ->toArray();

        // Get academic years
        $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();
        $curriculumYearLevels = $this->resolveCurriculumYearLevels($program->id);
        $curriculumSemesters = $this->resolveCurriculumSemesters($program->id);

        return view('program-head.curriculum.index', [
            'programs' => $programs,
            'subjects' => $subjects,
            'selectedProgramId' => $selectedProgramId,
            'groupedCurriculum' => $groupedCurriculum,
            'assignedMatrix' => $assignedMatrix,
            'academicYears' => $academicYears,
            'curriculumYearLevels' => $curriculumYearLevels,
            'curriculumSemesters' => $curriculumSemesters,
        ]);
    }

    /**
     * Assign subjects to curriculum.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'year_level' => ['required', 'integer'],
            'semester' => ['required', 'string'],
        ]);

        if ((int) $validated['program_id'] !== (int) $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $program = Program::findOrFail($validated['program_id']);
        $curriculumYearLevels = $this->resolveCurriculumYearLevels($program->id);
        $curriculumSemesters = $this->resolveCurriculumSemesters($program->id);

        $request->validate([
            'year_level' => ['required', 'integer', Rule::in($curriculumYearLevels->all())],
            'semester' => ['required', 'string', Rule::in($curriculumSemesters->pluck('value')->all())],
        ]);

        $yearLevel = (int) $validated['year_level'];
        $semester = strtolower(trim((string) $validated['semester']));

        // Verify all subjects belong to this department
        $subjects = Subject::whereIn('id', $validated['subject_ids'])
            ->where('department_id', $program->department_id)
            ->pluck('id')
            ->all();

        if (count($subjects) !== count($validated['subject_ids'])) {
            return back()->withErrors('Some subjects do not belong to your department.');
        }

        $alreadyAssigned = $program->subjects()
            ->whereIn('subjects.id', $subjects)
            ->pluck('subjects.id')
            ->all();

        if (!empty($alreadyAssigned)) {
            $programLabel = $program->program_code ?: $program->program_name;

            return back()
                ->withErrors([
                    'subject_ids' => "This subject has already been added to the {$programLabel} curriculum and cannot be assigned to another year level.",
                ])
                ->withInput();
        }

        $pivotData = collect($subjects)->mapWithKeys(fn ($id) => [
            $id => [
                'year_level' => $yearLevel,
                'semester' => $semester,
            ],
        ])->toArray();

        try {
            $program->subjects()->syncWithoutDetaching($pivotData);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $programLabel = $program->program_code ?: $program->program_name;

                return back()
                    ->withErrors([
                        'subject_ids' => "This subject has already been added to the {$programLabel} curriculum and cannot be assigned to another year level.",
                    ])
                    ->withInput();
            }

            throw $e;
        }

        return back()->with('success', 'Subjects assigned to curriculum successfully.');
    }

    /**
     * Resolve valid numeric year levels for curriculum assignment.
     */
    private function resolveCurriculumYearLevels(int $programId)
    {
        $yearLevels = YearLevel::query()
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

        if ($yearLevels->isEmpty()) {
            $yearLevels = DB::table('program_subjects')
                ->where('program_id', $programId)
                ->whereNotNull('year_level')
                ->distinct()
                ->pluck('year_level')
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->sort()
                ->values();
        }

        return $yearLevels;
    }

    /**
     * Resolve valid semester options for curriculum assignment.
     * Returns collection items: ['value' => 'normalized-key', 'label' => 'Display Label']
     */
    private function resolveCurriculumSemesters(int $programId)
    {
        $semesterNames = Semester::query()
            ->where('status', Semester::STATUS_ACTIVE)
            ->whereNotNull('name')
            ->orderBy('name')
            ->pluck('name')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($semesterNames->isEmpty()) {
            $semesterNames = DB::table('program_subjects')
                ->where('program_id', $programId)
                ->whereNotNull('semester')
                ->distinct()
                ->pluck('semester')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '')
                ->values();
        }

        return $semesterNames
            ->map(function (string $semesterName) {
                $value = strtolower($semesterName);

                return [
                    'value' => $value,
                    'label' => $this->formatSemesterLabel($semesterName),
                ];
            })
            ->unique('value')
            ->sortBy('label')
            ->values();
    }

    private function formatSemesterLabel(string $semesterName): string
    {
        $normalized = strtolower(trim($semesterName));

        return match ($normalized) {
            '1st' => '1st Semester',
            '2nd' => '2nd Semester',
            '3rd' => '3rd Semester',
            'summer' => 'Summer',
            default => $semesterName,
        };
    }
}
