<?php

namespace App\Http\Controllers\ProgramHead;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyWorkloadConfigurationRequest;
use App\Models\Department;
use App\Models\FacultyWorkloadConfiguration;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;

class FacultyWorkloadConfigurationController extends Controller
{
    /**
     * Display a listing of faculty workload configurations.
     */
    public function index(Request $request)
    {
        // Get program head's program
        $program = auth()->user()->program;
        $department = $program->department;

        // Get eligible users for workload configuration dropdown (same as Faculty Load Management)
        $facultyMembers = User::whereNotIn('role', [
                'admin',
                'student'
            ])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Build query with filters
        $query = FacultyWorkloadConfiguration::with(['faculty', 'program'])
            ->forProgram($program->id);

        // Search by faculty name
        if ($request->has('search') && $request->search) {
            $query->searchFaculty($request->search);
        }

        // Filter by department (for consistency, though all configs are in this program)
        if ($request->has('department') && $request->department) {
            $query->byDepartment($request->department);
        }

        // Filter by contract type
        if ($request->has('contract_type') && $request->contract_type) {
            $query->byContractType($request->contract_type);
        }

        // Paginate
        $configurations = $query->paginate(15);

        // If AJAX request, return only the table rows
        if ($request->ajax()) {
            $html = view('program-head.faculty-workload-configurations.partials.table-rows', [
                'configurations' => $configurations,
            ])->render();

            $pagination = $configurations->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'pagination' => $pagination,
            ]);
        }

        return view('program-head.faculty-workload-configurations.index', [
            'configurations' => $configurations,
            'facultyMembers' => $facultyMembers,
            'department' => $department,
            'program' => $program,
        ]);
    }

    /**
     * Show the form for creating a new workload configuration.
     */
    public function create()
    {
        $this->authorize('create', FacultyWorkloadConfiguration::class);

        $program = auth()->user()->program;
        $department = $program->department;

        $facultyMembers = User::whereNotIn('role', [
                'admin',
                'student'
            ])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'success' => true,
            'faculty' => $facultyMembers,
            'program' => $program,
        ]);
    }

    /**
     * Store a newly created workload configuration.
     */
    public function store(FacultyWorkloadConfigurationRequest $request)
    {
        $this->authorize('create', FacultyWorkloadConfiguration::class);

        try {
            $program = auth()->user()->program;
            $validated = $request->sanitized();

            // Check for duplicate
            $existing = FacultyWorkloadConfiguration::withTrashed()
                ->where('user_id', $validated['user_id'])
                ->where('program_id', $program->id)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'This faculty already has a workload configuration.',
                ], 422);
            }

            // Create configuration
            $configuration = FacultyWorkloadConfiguration::create([
                'user_id' => $validated['user_id'],
                'program_id' => $program->id,
                'contract_type' => $validated['contract_type'],
                'max_lecture_hours' => $validated['max_lecture_hours'],
                'max_lab_hours' => $validated['max_lab_hours'],
                'max_hours_per_day' => $validated['max_hours_per_day'],
                'available_days' => $validated['available_days'],
                'teaching_scheme' => $validated['teaching_scheme'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Faculty workload configuration saved successfully.',
                'configuration' => $configuration->load(['faculty', 'program']),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creating faculty workload configuration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the configuration.',
            ], 500);
        }
    }

    /**
     * Display the specified workload configuration.
     */
    public function show(FacultyWorkloadConfiguration $facultyWorkloadConfiguration)
    {
        $this->authorize('view', $facultyWorkloadConfiguration);

        $facultyWorkloadConfiguration->load([
            'faculty.department',
            'program.department',
        ]);

        return response()->json([
            'success' => true,
            'configuration' => $facultyWorkloadConfiguration,
        ]);
    }

    /**
     * Show the form for editing the workload configuration.
     */
    public function edit(FacultyWorkloadConfiguration $facultyWorkloadConfiguration)
    {
        $this->authorize('update', $facultyWorkloadConfiguration);

        $program = auth()->user()->program;
        $department = $program->department;

        $facultyMembers = User::whereNotIn('role', [
                'admin',
                'student'
            ])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return response()->json([
            'success' => true,
            'configuration' => $facultyWorkloadConfiguration->load([
                'faculty.department',
                'program.department',
            ]),
            'faculty' => $facultyMembers,
        ]);
    }

    /**
     * Update the specified workload configuration.
     */
    public function update(FacultyWorkloadConfigurationRequest $request, FacultyWorkloadConfiguration $facultyWorkloadConfiguration)
    {
        $this->authorize('update', $facultyWorkloadConfiguration);

        try {
            $validated = $request->sanitized();

            // Update configuration
            $facultyWorkloadConfiguration->update([
                'contract_type' => $validated['contract_type'],
                'max_lecture_hours' => $validated['max_lecture_hours'],
                'max_lab_hours' => $validated['max_lab_hours'],
                'max_hours_per_day' => $validated['max_hours_per_day'],
                'available_days' => $validated['available_days'],
                'teaching_scheme' => $validated['teaching_scheme'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Faculty workload configuration updated successfully.',
                'configuration' => $facultyWorkloadConfiguration->load(['faculty', 'program']),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating faculty workload configuration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the configuration.',
            ], 500);
        }
    }

    /**
     * Remove the specified workload configuration.
     */
    public function destroy(FacultyWorkloadConfiguration $facultyWorkloadConfiguration)
    {
        $this->authorize('delete', $facultyWorkloadConfiguration);

        try {
            $facultyWorkloadConfiguration->delete();

            return response()->json([
                'success' => true,
                'message' => 'Faculty workload configuration deleted successfully.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting faculty workload configuration: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the configuration.',
            ], 500);
        }
    }

    /**
     * Get faculty department (for modal auto-fill)
     */
    public function getFacultyDepartment(Request $request)
    {
        $facultyId = $request->input('faculty_id');
        $faculty = User::find($facultyId);

        if (!$faculty) {
            return response()->json([
                'success' => false,
                'message' => 'Faculty not found.',
            ], 404);
        }

        $department = $faculty->department;

        return response()->json([
            'success' => true,
            'department' => $department,
            'department_name' => $department?->department_name ?? 'N/A',
        ]);
    }
}
