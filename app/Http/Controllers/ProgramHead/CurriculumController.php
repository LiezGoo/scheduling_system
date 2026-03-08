<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ->sortBy(fn ($subject) => sprintf('%02d-%s-%s', $subject->pivot->year_level, $subject->pivot->semester, $subject->subject_code))
            ->groupBy(fn ($subject) => $subject->pivot->year_level)
            ->map(fn ($byYear) => $byYear->groupBy(fn ($subject) => $subject->pivot->semester));

        $assignedMatrix = $program->subjects
            ->mapToGroups(function ($subject) {
                return [$subject->id => ["{$subject->pivot->year_level}|{$subject->pivot->semester}"]];
            })
            ->map(fn ($group) => collect($group)->unique()->values())
            ->toArray();

        // Get academic years
        $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();

        return view('program-head.curriculum.index', [
            'programs' => $programs,
            'subjects' => $subjects,
            'selectedProgramId' => $selectedProgramId,
            'groupedCurriculum' => $groupedCurriculum,
            'assignedMatrix' => $assignedMatrix,
            'academicYears' => $academicYears,
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
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'string', 'in:1st,2nd,summer'],
        ]);

        if ((int) $validated['program_id'] !== (int) $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $program = Program::findOrFail($validated['program_id']);
        $yearLevel = $validated['year_level'];
        $semester = strtolower($validated['semester']);

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
}
