<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubjectController extends Controller
{
    use AuthorizesRequests;

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
                'pagination' => $subjects->withQueryString()->links()->render(),
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
