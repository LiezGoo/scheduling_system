<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Subject;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramSubjectController extends Controller
{
    /**
     * Show the curriculum management page.
     */
    public function index(Request $request): View
    {
        $programs = Program::orderBy('program_name')->get(['id', 'program_name']);
        $subjects = Subject::orderBy('subject_code')->get(['id', 'subject_code', 'subject_name']);

        $selectedProgramId = $request->integer('program_id') ?: $programs->first()?->id;
        $selectedProgram = $selectedProgramId
            ? Program::with(['subjects' => function ($query) {
                $query->withPivot(['year_level', 'semester']);
            }])->find($selectedProgramId)
            : null;

        $groupedCurriculum = $selectedProgram?->subjects
            ->sortBy(fn ($subject) => sprintf('%02d-%s-%s', $subject->pivot->year_level, $subject->pivot->semester, $subject->subject_code))
            ->groupBy(fn ($subject) => $subject->pivot->year_level)
            ->map(fn ($byYear) => $byYear->groupBy(fn ($subject) => $subject->pivot->semester))
            ?? collect();

        $assignedMatrix = $selectedProgram?->subjects
            ->mapToGroups(function ($subject) {
                return [$subject->id => ["{$subject->pivot->year_level}|{$subject->pivot->semester}"]];
            })
            ->map(fn ($group) => $group->unique()->values())
            ->toArray() ?? [];

        return view('admin.programs.curriculum', [
            'programs' => $programs,
            'subjects' => $subjects,
            'selectedProgramId' => $selectedProgramId,
            'groupedCurriculum' => $groupedCurriculum,
            'assignedMatrix' => $assignedMatrix,
        ]);
    }

    /**
     * Bulk assign subjects to a program for a specific term.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'semester' => ['required', 'string', 'in:1st,2nd,summer'],
        ]);

        $program = Program::findOrFail($validated['program_id']);
        $yearLevel = $validated['year_level'];
        $semester = strtolower($validated['semester']);

        $alreadyAssigned = $program->subjects()
            ->wherePivot('year_level', $yearLevel)
            ->wherePivot('semester', $semester)
            ->pluck('subjects.id')
            ->all();

        $subjectIds = array_values(array_diff($validated['subject_ids'], $alreadyAssigned));

        if (empty($subjectIds)) {
            return back()->with('error', 'All selected subjects are already assigned for this year/semester.');
        }

        $pivotData = collect($subjectIds)->mapWithKeys(fn ($id) => [
            $id => [
                'year_level' => $yearLevel,
                'semester' => $semester,
            ],
        ])->toArray();

        try {
            $program->subjects()->syncWithoutDetaching($pivotData);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return back()->with('error', 'Duplicate curriculum entries detected. Please review selections.');
            }
            throw $e;
        }

        return back()->with('success', 'Subjects assigned to curriculum successfully.');
    }
}
