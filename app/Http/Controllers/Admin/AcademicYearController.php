<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class AcademicYearController extends Controller
{
    /**
     * Display a listing of academic years and semesters.
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::with('semesters')
            ->orderByDesc('start_year')
            ->get();

        $selectedAcademicYear = null;
        if ($request->filled('academic_year_id')) {
            $selectedAcademicYear = AcademicYear::with('semesters')
                ->find($request->academic_year_id);
        } else {
            // Default to active academic year
            $selectedAcademicYear = AcademicYear::active()->with('semesters')->first();
        }

        return view('admin.academic-years.index', compact('academicYears', 'selectedAcademicYear'));
    }

    /**
     * Store a newly created academic year.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'end_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                'gt:start_year', // end_year must be greater than start_year
            ],
        ]);

        try {
            // Check if combination already exists
            $exists = AcademicYear::where('start_year', $validated['start_year'])
                ->where('end_year', $validated['end_year'])
                ->exists();

            if ($exists) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This academic year already exists.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'This academic year already exists.');
            }

            // Auto-generate name
            $validated['name'] = $validated['start_year'] . '-' . $validated['end_year'];

            $academicYear = AcademicYear::create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Academic year created successfully.',
                    'academic_year' => $academicYear,
                ]);
            }

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Academic year created successfully!');
        } catch (\Exception $e) {
            Log::error('Academic year creation failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create academic year.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to create academic year.');
        }
    }

    /**
     * Display the specified academic year.
     */
    public function show(AcademicYear $academicYear)
    {
        $academicYear->load('semesters');

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'academic_year' => $academicYear,
            ]);
        }

        return view('admin.academic-years.show', compact('academicYear'));
    }

    /**
     * Update the specified academic year.
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'start_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
            ],
            'end_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                'gt:start_year',
            ],
        ]);

        try {
            // Check if combination already exists (excluding current record)
            $exists = AcademicYear::where('start_year', $validated['start_year'])
                ->where('end_year', $validated['end_year'])
                ->where('id', '!=', $academicYear->id)
                ->exists();

            if ($exists) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This academic year already exists.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'This academic year already exists.');
            }

            // Auto-generate name
            $validated['name'] = $validated['start_year'] . '-' . $validated['end_year'];

            $academicYear->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Academic year updated successfully.',
                    'academic_year' => $academicYear,
                ]);
            }

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Academic year updated successfully!');
        } catch (\Exception $e) {
            Log::error('Academic year update failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update academic year.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update academic year.');
        }
    }

    /**
     * Remove the specified academic year.
     */
    public function destroy(Request $request, AcademicYear $academicYear)
    {
        try {
            // TODO: Add validation to prevent deletion if used in schedules
            // if ($academicYear->schedules()->exists()) {
            //     throw new \Exception('Cannot delete academic year that is being used in schedules.');
            // }

            $academicYear->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Academic year deleted successfully.',
                ]);
            }

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Academic year deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Academic year deletion failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Activate the specified academic year.
     * This deactivates all other academic years and their semesters.
     */
    public function activate(Request $request, AcademicYear $academicYear)
    {
        try {
            $academicYear->activate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Academic year activated successfully.',
                ]);
            }

            return redirect()->route('admin.academic-years.index')
                ->with('success', 'Academic year activated successfully!');
        } catch (\Exception $e) {
            Log::error('Academic year activation failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate academic year.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to activate academic year.');
        }
    }

    /**
     * Store a new semester for an academic year.
     */
    public function storeSemester(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::in(Semester::VALID_NAMES),
            ],
        ]);

        try {
            // Check if semester already exists for this academic year
            $exists = $academicYear->semesters()
                ->where('name', $validated['name'])
                ->exists();

            if ($exists) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This semester already exists for this academic year.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'This semester already exists for this academic year.');
            }

            $semester = $academicYear->semesters()->create($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semester created successfully.',
                    'semester' => $semester,
                ]);
            }

            return redirect()->route('admin.academic-years.index', ['academic_year_id' => $academicYear->id])
                ->with('success', 'Semester created successfully!');
        } catch (\Exception $e) {
            Log::error('Semester creation failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create semester.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to create semester.');
        }
    }

    /**
     * Update a semester.
     */
    public function updateSemester(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                Rule::in(Semester::VALID_NAMES),
            ],
        ]);

        try {
            // Check if semester name already exists for this academic year (excluding current)
            $exists = Semester::where('academic_year_id', $semester->academic_year_id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $semester->id)
                ->exists();

            if ($exists) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This semester already exists for this academic year.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'This semester already exists for this academic year.');
            }

            $semester->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semester updated successfully.',
                    'semester' => $semester,
                ]);
            }

            return redirect()->route('admin.academic-years.index', ['academic_year_id' => $semester->academic_year_id])
                ->with('success', 'Semester updated successfully!');
        } catch (\Exception $e) {
            Log::error('Semester update failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update semester.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to update semester.');
        }
    }

    /**
     * Delete a semester.
     */
    public function destroySemester(Request $request, Semester $semester)
    {
        try {
            // TODO: Add validation to prevent deletion if used in schedules
            // if ($semester->schedules()->exists()) {
            //     throw new \Exception('Cannot delete semester that is being used in schedules.');
            // }

            $academicYearId = $semester->academic_year_id;
            $semester->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semester deleted successfully.',
                ]);
            }

            return redirect()->route('admin.academic-years.index', ['academic_year_id' => $academicYearId])
                ->with('success', 'Semester deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Semester deletion failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Activate a semester.
     * This deactivates all other semesters in the same academic year.
     */
    public function activateSemester(Request $request, Semester $semester)
    {
        try {
            $semester->activate();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semester activated successfully.',
                ]);
            }

            return redirect()->route('admin.academic-years.index', ['academic_year_id' => $semester->academic_year_id])
                ->with('success', 'Semester activated successfully!');
        } catch (\Exception $e) {
            Log::error('Semester activation failed: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate semester.',
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to activate semester.');
        }
    }
}
