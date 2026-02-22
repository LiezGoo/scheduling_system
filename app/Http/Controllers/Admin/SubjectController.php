<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Schema;

class SubjectController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of subjects with optional filters.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Subject::class);
        $query = Subject::with('department');

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered subjects
        $subjects = $query->orderBy('subject_code')->paginate($perPage)->appends($request->query());

        // Get all departments for filter dropdown
        $departmentNameColumn = Schema::hasColumn('departments', 'department_name')
            ? 'department_name'
            : 'name';
        $departments = Department::orderBy($departmentNameColumn)->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => $subjects->withQueryString()->links()->render(),
            ]);
        }

        return view('admin.subjects.index', compact('subjects', 'departments'));
    }

    /**
     * Display the specified subject.
     */
    public function show(Subject $subject)
    {
        $this->authorize('view', $subject);
        $subject->load('department');
        return view('admin.subjects.show', compact('subject'));
    }

    /**
     * Store a newly created subject.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Subject::class);
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')->where('department_id', $request->department_id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')->where('department_id', $request->department_id),
            ],
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Lecture hours and lab hours cannot both be 0.',
            ], 422);
        }

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $subject = Subject::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully!',
                'subject' => $subject->load('department'),
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
        $this->authorize('update', $subject);
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')
                    ->where('department_id', $request->department_id)
                    ->ignore($subject->id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')
                    ->where('department_id', $request->department_id)
                    ->ignore($subject->id),
            ],
            'units' => 'required|numeric|min:0|max:10',
            'lecture_hours' => 'required|numeric|min:0|max:20',
            'lab_hours' => 'required|numeric|min:0|max:20',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validated['lecture_hours'] <= 0 && $validated['lab_hours'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Lecture hours and lab hours cannot both be 0.',
            ], 422);
        }

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $subject->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully!',
                'subject' => $subject->load('department'),
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
        $this->authorize('delete', $subject);
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
