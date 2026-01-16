<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ProgramController extends Controller
{
    /**
     * Display a listing of programs with optional filters.
     */
    public function index(Request $request)
    {
        $query = Program::with(['departments']);

        // Filter by search (program code or name)
        if ($request->filled('q')) {
            $search = '%' . $request->q . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('program_code', 'LIKE', $search)
                    ->orWhere('program_name', 'LIKE', $search);
            });
        }

        // Filter by department
        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        // Get filtered programs
        $programs = $query->orderBy('program_code')->paginate($perPage)->appends($request->query());

        // Get all departments for filter dropdowns
        $departments = Department::orderBy('department_code')->get();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'rows' => view('admin.programs.partials.table-rows', compact('programs'))->render(),
                'pagination' => $programs->withQueryString()->links()->render(),
                'summary' => view('admin.programs.partials.summary', compact('programs'))->render(),
            ]);
        }

        return view('admin.programs.index', compact('programs', 'departments'));
    }

    /**
     * Display the specified program.
     */
    public function show(Program $program)
    {
        $program->load(['departments']);

        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'program' => [
                    'id' => $program->id,
                    'program_code' => $program->program_code,
                    'program_name' => $program->program_name,
                    'department_id' => $program->department_id,
                    'department_name' => $program->departments->department_name ?? 'N/A',
                    'created_at' => $program->created_at,
                    'updated_at' => $program->updated_at,
                ]
            ]);
        }

        return view('admin.programs.show', compact('program'));
    }

    /**
     * Store a newly created program.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'program_code' => 'required|string|max:50|unique:programs,program_code',
            'program_name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        try {
            $program = Program::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Program created successfully!',
                'program' => $program->load(['departments']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating program', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified program.
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'program_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('programs', 'program_code')->ignore($program->id),
            ],
            'program_name' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
        ]);

        try {
            $program->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Program updated successfully!',
                'program' => $program->load(['departments']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating program', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update program: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified program.
     */
    public function destroy(Program $program)
    {
        try {
            // Check if program has subjects assigned
            if ($program->subjects()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete program with assigned subjects. Please remove curriculum entries first.',
                ], 422);
            }

            $program->delete();

            return response()->json([
                'success' => true,
                'message' => 'Program deleted successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting program', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete program: ' . $e->getMessage(),
            ], 500);
        }
    }
}
