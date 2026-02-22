<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of subjects for the department head's department.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('viewAny', Subject::class);

        // Department heads can only VIEW subjects from their department
        if (!$user->isDepartmentHead()) {
            abort(403, 'Unauthorized access.');
        }

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $query = Subject::with(['department', 'creator'])
            ->forDepartment($department->id);

        // Filter by search (subject code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('subject_code', 'LIKE', $search)
                    ->orWhere('subject_name', 'LIKE', $search);
            });
        }

        // Filter by active status (default: show all)
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
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
                'html' => view('department-head.subjects.partials.table-rows', compact('subjects'))->render(),
                'pagination' => $subjects->withQueryString()->links()->render(),
            ]);
        }

        return view('department-head.subjects.index', [
            'subjects' => $subjects,
            'departmentName' => $departmentName,
        ]);
    }

    /**
     * Display the specified subject details.
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

        // Ensure subject belongs to department head's department
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
        return view('department-head.subjects.show', compact('subject'));
    }

    /**
     * Store a newly created subject in the department head's department.
     */
    public function store(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('create', Subject::class);

        $department = $user->getInferredDepartment();

        if (!$department) {
            abort(403, 'No department assigned.');
        }

        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')->where('department_id', $department->id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')->where('department_id', $department->id),
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
            // Automatically set department_id and created_by
            $validated['department_id'] = $department->id;
            $validated['created_by'] = $user->id;
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
     * Update the specified subject in the department head's department.
     */
    public function update(Request $request, Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('update', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to department head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'subject_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('subjects', 'subject_code')
                    ->where('department_id', $department->id)
                    ->ignore($subject->id),
            ],
            'subject_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subjects', 'subject_name')
                    ->where('department_id', $department->id)
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
     * Remove/deactivate the specified subject in the department head's department.
     */
    public function destroy(Subject $subject)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        $this->authorize('delete', $subject);

        $department = $user->getInferredDepartment();

        // Ensure subject belongs to department head's department
        if (!$department || $subject->department_id !== $department->id) {
            abort(403, 'Unauthorized access.');
        }

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
