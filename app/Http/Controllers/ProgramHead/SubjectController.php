<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    /**
     * Display a listing of subjects for the program head's program.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Program heads can only manage subjects for their assigned program
        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $query = Subject::with('program')
            ->where('program_id', $user->program_id);

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
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

        // Get program information
        $program = Program::findOrFail($user->program_id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('program-head.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => $subjects->withQueryString()->links()->render(),
            ]);
        }

        return view('program-head.subjects.index', compact('subjects', 'program'));
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isProgramHead() || !$user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:255',
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'nullable|numeric|min:0|max:40',
            'lab_hours' => 'nullable|numeric|min:0|max:40',
            'year_level' => 'required|integer|in:1,2,3,4',
            'semester' => 'required|integer|in:1,2',
        ]);

        // Force program_id to be the program head's program
        $validated['program_id'] = $user->program_id;
        $validated['lecture_hours'] = $validated['lecture_hours'] ?? 0;
        $validated['lab_hours'] = $validated['lab_hours'] ?? 0;

        $subject = Subject::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject created successfully.',
            'subject' => $subject,
        ], 201);
    }

    /**
     * Display the specified subject as JSON for modal.
     */
    public function show(Request $request, Subject $subject)
    {
        $user = Auth::user();

        // Ensure subject belongs to program head's program
        if (!$user->isProgramHead() || $subject->program_id !== $user->program_id) {
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
                    'year_level' => $subject->year_level,
                    'semester' => $subject->semester,
                ]
            ]);
        }

        $subject->load('program');
        return view('program-head.subjects.show', compact('subject'));
    }

    /**
     * Update the specified subject.
     */
    public function update(Request $request, Subject $subject)
    {
        $user = Auth::user();

        // Ensure subject belongs to program head's program
        if (!$user->isProgramHead() || $subject->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'subject_code' => ['required', 'string', 'max:50', Rule::unique('subjects')->ignore($subject->id)],
            'subject_name' => 'required|string|max:255',
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'nullable|numeric|min:0|max:40',
            'lab_hours' => 'nullable|numeric|min:0|max:40',
            'year_level' => 'required|integer|in:1,2,3,4',
            'semester' => 'required|integer|in:1,2',
        ]);

        $validated['lecture_hours'] = $validated['lecture_hours'] ?? 0;
        $validated['lab_hours'] = $validated['lab_hours'] ?? 0;

        $subject->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'subject' => $subject->fresh(),
        ]);
    }

    /**
     * Remove the specified subject.
     */
    public function destroy(Subject $subject)
    {
        $user = Auth::user();

        // Ensure subject belongs to program head's program
        if (!$user->isProgramHead() || $subject->program_id !== $user->program_id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject. It may be in use.',
            ], 422);
        }
    }
}
