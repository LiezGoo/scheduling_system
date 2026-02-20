<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Subject;
use App\Services\FacultyLoadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacultyLoadController extends Controller
{
    protected FacultyLoadService $facultyLoadService;

    public function __construct(FacultyLoadService $facultyLoadService)
    {
        $this->facultyLoadService = $facultyLoadService;
    }

    /**
     * Display Faculty Load Management for program head's program.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        // Build faculty loads query - scoped to program head's department
        $query = DB::table('faculty_subjects')
            ->join('users', 'faculty_subjects.user_id', '=', 'users.id')
            ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
            ->join('departments', 'subjects.department_id', '=', 'departments.id')
            ->select(
                'faculty_subjects.id',
                'users.id as user_id',
                'users.full_name',
                'users.school_id',
                'users.role',
                'subjects.id as subject_id',
                'subjects.subject_code',
                'subjects.subject_name',
                'subjects.units',
                'departments.id as department_id',
                'departments.department_name',
                'faculty_subjects.lecture_hours',
                'faculty_subjects.lab_hours',
                'faculty_subjects.computed_units',
                'faculty_subjects.max_load_units',
                'faculty_subjects.created_at'
            )
            ->where('subjects.department_id', $department->id);

        // Apply filters
        if ($request->filled('faculty')) {
            $query->where('users.id', $request->faculty);
        }

        if ($request->filled('role')) {
            $query->where('users.role', $request->role);
        }

        if ($request->filled('subject')) {
            $query->where('subjects.id', $request->subject);
        }

        if ($request->filled('department')) {
            $query->where('departments.id', $request->department);
        }

        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $facultyLoads = $query->orderBy('users.full_name')->paginate($perPage);

        $departments = Department::where('id', $department->id)
            ->orderBy('department_name')
            ->get();

        // Get subjects only from program head's department
        $subjects = Subject::where('department_id', $department->id)
            ->orderBy('subject_code')
            ->get();

        // Get eligible faculty
        $eligibleFaculty = User::eligibleInstructors()->active()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Get summary statistics
        $summary = $this->facultyLoadService->getFacultyLoadSummary();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('program-head.faculty-load.partials.table-rows', compact('facultyLoads'))->render(),
                'pagination' => $facultyLoads->withQueryString()->links(),
            ]);
        }

        return view('program-head.faculty-load.index', [
            'facultyLoads' => $facultyLoads,
            'departments' => $departments,
            'subjects' => $subjects,
            'eligibleFaculty' => $eligibleFaculty,
            'summary' => $summary,
            'currentFilters' => [
                'faculty' => $request->input('faculty'),
                'department' => $request->input('department'),
                'role' => $request->input('role'),
                'subject' => $request->input('subject'),
            ],
        ]);
    }

    /**
     * Assign a subject to an instructor.
     */
    public function assignSubject(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'max_load_units' => 'nullable|integer|min:1',
        ]);

        // Verify subject belongs to program head's department
        $subject = Subject::findOrFail($validated['subject_id']);
        if ($subject->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Subject does not belong to your department.'
            ], 403);
        }

        try {
            $result = $this->facultyLoadService->assignSubjectToInstructor(
                $validated['user_id'],
                $validated['subject_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                $validated['max_load_units']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error assigning subject", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }

    /**
     * Get faculty load details.
     */
    public function getDetails($facultyLoadId)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $load = DB::table('faculty_subjects')
                ->join('users', 'faculty_subjects.user_id', '=', 'users.id')
                ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
                ->join('departments', 'subjects.department_id', '=', 'departments.id')
                ->select(
                    'faculty_subjects.*',
                    'users.id as user_id',
                    'users.full_name',
                    'users.school_id',
                    'users.role',
                    'subjects.id as subject_id',
                    'subjects.subject_code',
                    'subjects.subject_name',
                    'subjects.units',
                    'departments.id as department_id',
                    'departments.department_name'
                )
                ->where('faculty_subjects.id', $facultyLoadId)
                ->where('subjects.department_id', $department->id)
                ->first();

            if (!$load) {
                return response()->json([
                    'success' => false,
                    'message' => 'Faculty load not found or unauthorized.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'id' => $load->id,
                'faculty' => [
                    'id' => $load->user_id,
                    'full_name' => $load->full_name,
                    'school_id' => $load->school_id,
                    'role' => $load->role,
                ],
                'subject' => [
                    'id' => $load->subject_id,
                    'subject_code' => $load->subject_code,
                    'subject_name' => $load->subject_name,
                    'units' => $load->units,
                ],
                'department' => [
                    'id' => $load->department_id,
                    'department_name' => $load->department_name,
                ],
                'lecture_hours' => $load->lecture_hours ?? 0,
                'lab_hours' => $load->lab_hours ?? 0,
                'computed_units' => $load->computed_units ?? 0,
                'max_load_units' => $load->max_load_units,
            ]);
        } catch (\Exception $e) {
            Log::error("Error fetching faculty load details", ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }

    /**
     * Update faculty load constraints.
     */
    public function updateConstraints(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'faculty_load_id' => 'required|integer',
            'lecture_hours' => 'required|integer|min:0|max:40',
            'lab_hours' => 'required|integer|min:0|max:40',
            'max_load_units' => 'nullable|integer|min:1',
        ]);

        try {
            // Verify the faculty load belongs to program head's department
            $load = DB::table('faculty_subjects')
                ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
                ->where('faculty_subjects.id', $validated['faculty_load_id'])
                ->where('subjects.department_id', $department->id)
                ->select('faculty_subjects.id')
                ->first();

            if (!$load) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.'
                ], 403);
            }

            $result = $this->facultyLoadService->updateConstraints(
                $validated['faculty_load_id'],
                $validated['lecture_hours'],
                $validated['lab_hours'],
                $validated['max_load_units']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error updating constraints", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }

    /**
     * Remove subject assignment.
     */
    public function removeAssignment(Request $request)
    {
        $user = Auth::user();

        $department = $user->getInferredDepartment();

        if (!$user->isProgramHead() || !$department) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        // Verify subject belongs to program head's department
        $subject = Subject::findOrFail($validated['subject_id']);
        if ($subject->department_id !== $department->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        try {
            $result = $this->facultyLoadService->removeSubjectAssignment(
                $validated['user_id'],
                $validated['subject_id']
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }
        } catch (\Exception $e) {
            Log::error("Error removing assignment", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred.'
            ], 500);
        }
    }
}
