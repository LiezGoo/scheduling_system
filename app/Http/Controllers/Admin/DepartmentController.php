<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class DepartmentController extends Controller
{
    /**
     * Display a listing of departments with optional filters.
     */
    public function index(Request $request)
    {
        $query = Department::query();

        // Filter by search (department code or name)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('department_code', 'LIKE', $search)
                    ->orWhere('department_name', 'LIKE', $search);
            });
        }

        // Get per page value (default 15)
        $perPage = $request->input('per_page', 15);
        $perPage = in_array($perPage, [5, 10, 15, 25]) ? $perPage : 15;

        // Get filtered departments
        $departments = $query->orderBy('department_code')->paginate($perPage)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'html' => view('admin.departments.partials.table-rows', compact('departments'))->render(),
                'pagination' => $departments->withQueryString()->links()->render(),
            ]);
        }

        return view('admin.departments.index', compact('departments'));
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        // Return JSON for AJAX requests
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'department' => [
                    'id' => $department->id,
                    'department_code' => $department->department_code,
                    'department_name' => $department->department_name,
                    'created_at' => $department->created_at,
                    'updated_at' => $department->updated_at,
                ]
            ]);
        }

        return view('admin.departments.show', compact('department'));
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:50|unique:departments,department_code',
            'department_name' => 'required|string|max:255',
        ]);

        try {
            $department = Department::create($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Department created successfully!',
                    'department' => $department,
                ]);
            }

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department created successfully!');
        } catch (\Exception $e) {
            Log::error('Department creation failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create department: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create department. Please try again.');
        }
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'department_code')->ignore($department->id),
            ],
            'department_name' => 'required|string|max:255',
        ]);

        try {
            $department->update($validated);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Department updated successfully!',
                    'department' => $department,
                ]);
            }

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department updated successfully!');
        } catch (\Exception $e) {
            Log::error('Department update failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update department: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update department. Please try again.');
        }
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department)
    {
        try {
            // Check if department has associated programs
            if ($department->programs()->exists()) {
                if (request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete department with associated programs. Please delete or reassign all programs first.',
                    ], 422);
                }

                return redirect()->back()
                    ->with('error', 'Cannot delete department with associated programs.');
            }

            $department->delete();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Department deleted successfully!',
                ]);
            }

            return redirect()->route('admin.departments.index')
                ->with('success', 'Department deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Department deletion failed: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete department: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete department. Please try again.');
        }
    }
}
