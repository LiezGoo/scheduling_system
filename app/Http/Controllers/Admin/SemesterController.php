<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SemesterController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $academicYears = AcademicYear::orderByDesc('start_year')->get();

        $semesters = Semester::with('academicYear')
            ->when($request->filled('academic_year_id'), function ($query) use ($request) {
                $query->where('academic_year_id', $request->integer('academic_year_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.semesters.index', [
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'filters' => [
                'academic_year_id' => $request->input('academic_year_id'),
                'status' => $request->input('status'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50', Rule::unique('semesters')->where(function ($query) use ($request) {
                return $query->where('academic_year_id', $request->input('academic_year_id'));
            })],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', Rule::in([Semester::STATUS_ACTIVE, Semester::STATUS_INACTIVE])],
        ]);

        $status = $validated['status'] ?? Semester::STATUS_INACTIVE;

        DB::transaction(function () use ($validated, $status) {
            if ($status === Semester::STATUS_ACTIVE) {
                Semester::where('academic_year_id', $validated['academic_year_id'])
                    ->update(['status' => Semester::STATUS_INACTIVE]);
            }

            Semester::create([
                'academic_year_id' => $validated['academic_year_id'],
                'name' => trim($validated['name']),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $status,
            ]);
        });

        return redirect()->route('admin.semesters.index')->with('success', 'Semester created successfully.');
    }

    public function update(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:50', Rule::unique('semesters')->where(function ($query) use ($request) {
                return $query->where('academic_year_id', $request->input('academic_year_id'));
            })->ignore($semester->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', Rule::in([Semester::STATUS_ACTIVE, Semester::STATUS_INACTIVE])],
        ]);

        $status = $validated['status'] ?? Semester::STATUS_INACTIVE;

        DB::transaction(function () use ($semester, $validated, $status) {
            if ($status === Semester::STATUS_ACTIVE) {
                Semester::where('academic_year_id', $validated['academic_year_id'])
                    ->where('id', '!=', $semester->id)
                    ->update(['status' => Semester::STATUS_INACTIVE]);
            }

            $semester->update([
                'academic_year_id' => $validated['academic_year_id'],
                'name' => trim($validated['name']),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $status,
            ]);
        });

        return redirect()->route('admin.semesters.index')->with('success', 'Semester updated successfully.');
    }

    public function toggleStatus(Semester $semester)
    {
        DB::transaction(function () use ($semester) {
            if ($semester->status === Semester::STATUS_ACTIVE) {
                $semester->update(['status' => Semester::STATUS_INACTIVE]);
                return;
            }

            Semester::where('academic_year_id', $semester->academic_year_id)
                ->where('id', '!=', $semester->id)
                ->update(['status' => Semester::STATUS_INACTIVE]);

            $semester->update(['status' => Semester::STATUS_ACTIVE]);
        });

        return redirect()->route('admin.semesters.index')->with('success', 'Semester status updated successfully.');
    }

    public function destroy(Semester $semester)
    {
        $academicYearName = optional($semester->academicYear)->name;

        $isUsedInFacultyLoads = DB::table('instructor_loads')
            ->where('academic_year_id', $semester->academic_year_id)
            ->where('semester', $semester->name)
            ->exists();

        $isUsedInSubjects = DB::table('program_subjects')
            ->where('semester', $semester->name)
            ->exists();

        $isUsedInSchedules = DB::table('schedules')
            ->where('academic_year', $academicYearName)
            ->where('semester', $semester->name)
            ->exists();

        if ($isUsedInFacultyLoads || $isUsedInSubjects || $isUsedInSchedules) {
            return redirect()->route('admin.semesters.index')
                ->with('error', 'Cannot delete semester because it is used in faculty loads, subjects, or schedules.');
        }

        $semester->delete();

        return redirect()->route('admin.semesters.index')->with('success', 'Semester deleted successfully.');
    }
}
