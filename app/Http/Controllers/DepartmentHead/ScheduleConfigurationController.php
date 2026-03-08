<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\ScheduleConfiguration;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ScheduleConfigurationController extends Controller
{
    /**
     * List schedule configurations created by the authenticated department head.
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isDepartmentHead()) {
            return response()->json([
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $configurations = ScheduleConfiguration::with(['program:id,program_name', 'academicYear:id,name'])
            ->where('department_head_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $configurations,
        ]);
    }

    /**
     * Store a new schedule configuration.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isDepartmentHead() || !$user->department_id) {
            return response()->json([
                'message' => 'Unauthorized access. Only Department Heads can configure schedules.',
            ], 403);
        }

        $validated = $request->validate([
            'program_id' => 'required|integer|exists:programs,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester' => [
                'required',
                'string',
                Rule::exists('semesters', 'name'),
            ],
            'year_level' => 'required|integer|min:1|max:4',
            'number_of_blocks' => 'required|integer|min:1',
        ]);

        $program = Program::find($validated['program_id']);
        if (!$program || $program->department_id !== $user->department_id) {
            return response()->json([
                'message' => 'The selected program does not belong to your department.',
            ], 422);
        }

        $configuration = ScheduleConfiguration::create([
            'program_id' => $validated['program_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'semester' => $validated['semester'],
            'year_level' => $validated['year_level'],
            'number_of_blocks' => $validated['number_of_blocks'],
            'department_head_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Schedule configuration saved successfully.',
            'data' => $configuration,
        ], 201);
    }
}
