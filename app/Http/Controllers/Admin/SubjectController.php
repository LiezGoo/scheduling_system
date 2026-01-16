<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Display a listing of subjects with optional filters.
     */
    public function index(Request $request)
    {
        $query = Subject::with('program');

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
        }

        // Filter by program
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered subjects
        $subjects = $query->orderBy('subject_code')->paginate($perPage)->appends($request->query());

        // Get all programs for filter dropdown
        $programs = Program::orderBy('program_name')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => $subjects->withQueryString()->links()->render(),
            ]);
        }

        return view('admin.subjects.index', compact('subjects', 'programs'));
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject)
    {
        $subject->load('program');
        return view('admin.subjects.show', compact('subject'));
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:255',
            'program_id' => 'nullable|exists:programs,id',
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'year_level' => 'required|integer|min:1|max:4',
            'semester' => 'required|integer|min:1|max:2',
        ]);

        try {
            $subject = Subject::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully!',
                'subject' => $subject->load('program'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')->ignore($subject->id),
            ],
            'subject_name' => 'required|string|max:255',
            'program_id' => 'nullable|exists:programs,id',
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'year_level' => 'required|integer|min:1|max:4',
            'semester' => 'required|integer|min:1|max:2',
        ]);

        try {
            $subject->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully!',
                'subject' => $subject->load('program'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject)
    {
        try {
            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject: ' . $e->getMessage(),
            ], 500);
        }
    }
}
