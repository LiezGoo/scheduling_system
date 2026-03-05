<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Models\Room;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;
use App\Services\ScheduleGenerationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ScheduleController extends Controller
{
    use AuthorizesRequests;

    protected NotificationService $notificationService;
    protected ScheduleGenerationService $scheduleGenerationService;

    public function __construct(NotificationService $notificationService, ScheduleGenerationService $scheduleGenerationService)
    {
        $this->notificationService = $notificationService;
        $this->scheduleGenerationService = $scheduleGenerationService;
    }

    /**
     * Display a listing of schedules for department head's department.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isDepartmentHead() || !$user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        // Fetch dynamic data for generation form
        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->get();
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();
        }

        // Get semesters - fetch from database
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $semesters = [];
        if ($activeAcademicYear) {
            $semesters = Semester::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Semester::STATUS_ACTIVE)
                ->get()
                ->mapWithKeys(function ($semester) {
                    return [$semester->name => $semester->name];
                })
                ->toArray();
        }

        // Get all programs in this department
        $programs = Program::where('department_id', $user->department_id)
            ->orderBy('program_name')
            ->get();

        // Get schedules for all programs in department with filters
        $query = Schedule::with(['program', 'creator'])
            ->whereHas('program', function ($q) use ($user) {
                $q->where('department_id', $user->department_id);
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by program
        if ($request->filled('program_id')) {
            $query->where('program_id', $request->program_id);
        }

        // Filter by academic year
        if ($request->filled('academic_year_id')) {
            $academicYear = AcademicYear::find($request->academic_year_id);
            if ($academicYear) {
                $query->where('academic_year', $academicYear->name);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by semester
        if ($request->filled('semester')) {
            $query->where('semester', $request->semester);
        }

        // Filter by year level
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get year levels from programs in department
        $yearLevels = [1, 2, 3, 4];

        // Get faculty from department
        $faculty = User::where('department_id', $user->department_id)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        // Get all rooms
        $rooms = Room::orderBy('room_code')->get();

        return view('department-head.schedules.index', compact(
            'schedules',
            'academicYears',
            'semesters',
            'programs',
            'yearLevels',
            'faculty',
            'rooms'
        ));
    }

    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('view', $schedule);

        // Ensure schedule belongs to department head's department
        if (!$user->isDepartmentHead() || $schedule->program?->department_id !== $user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        $schedule->load(['items.subject', 'items.instructor', 'items.room.building', 'program']);

        return view('department-head.schedules.show', compact('schedule'));
    }

    /**
     * Show the schedule generation form using Genetic Algorithm.
     */
    public function generate()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        if (!$user->isDepartmentHead() || !$user->department_id) {
            abort(403, 'Unauthorized access. Only Department Heads can generate schedules.');
        }

        // Fetch dynamic data for generation form
        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_year', 'desc')
            ->get();
        if ($academicYears->isEmpty()) {
            $academicYears = AcademicYear::orderBy('start_year', 'desc')->get();
        }

        // Get semesters - fetch from database
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();
        $semesters = [];
        if ($activeAcademicYear) {
            $semesters = Semester::where('academic_year_id', $activeAcademicYear->id)
                ->where('status', Semester::STATUS_ACTIVE)
                ->get()
                ->mapWithKeys(function ($semester) {
                    return [$semester->name => $semester->name];
                })
                ->toArray();
        }

        // Get all programs in this department
        $programs = Program::where('department_id', $user->department_id)
            ->with('department')
            ->orderBy('program_name')
            ->get();

        // Get faculty from department
        $faculty = User::where('department_id', $user->department_id)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        // Get all rooms
        $rooms = Room::orderBy('room_code')->get();

        return view('department-head.schedules.generate', compact(
            'academicYears',
            'semesters',
            'programs',
            'faculty',
            'rooms'
        ));
    }

    /**
     * Execute schedule generation using Genetic Algorithm.
     */
    public function executeGeneration(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        if (!$user->isDepartmentHead() || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only Department Heads can generate schedules.',
            ], 403);
        }

        $validated = $request->validate([
            'program_id' => 'required|integer|exists:programs,id',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'semester' => 'required|string',
            'year_level' => 'required|integer|min:1|max:4',
            'block' => 'nullable|string|max:50',
            'population_size' => 'nullable|integer|min:10|max:500',
            'generations' => 'nullable|integer|min:10|max:1000',
            'mutation_rate' => 'nullable|integer|min:1|max:100',
            'crossover_rate' => 'nullable|integer|min:1|max:100',
            'elite_size' => 'nullable|integer|min:1|max:50',
        ]);

        // Verify program belongs to department
        $program = Program::find($validated['program_id']);
        if (!$program || $program->department_id !== $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid program selection.',
            ], 403);
        }

        try {
            // Prepare parameters for GA
            $parameters = [
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
                'program_id' => $validated['program_id'],
                'year_level' => $validated['year_level'],
                'block_section' => $validated['block'] ?? 'Block 1',
                'created_by' => $user->id,
                'population_size' => $validated['population_size'] ?? 50,
                'generations' => $validated['generations'] ?? 100,
                'mutation_rate' => $validated['mutation_rate'] ?? 15,
                'crossover_rate' => $validated['crossover_rate'] ?? 80,
                'elite_size' => $validated['elite_size'] ?? 5,
            ];

            // Execute GA
            $result = $this->scheduleGenerationService->generateSchedule($parameters);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Schedule generated successfully.',
                    'data' => [
                        'schedule_id' => $result['schedule_id'],
                        'fitness_score' => $result['fitness_score'],
                        'items_count' => count($result['genes'] ?? []),
                        'faculty_loads' => $result['faculty_loads'] ?? [],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Schedule generation failed: ' . $result['error'],
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Schedule generation error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during schedule generation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalize and publish schedule.
     */
    public function finalize(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('finalize', $schedule);

        // Ensure schedule belongs to department head's department
        if (!$user->isDepartmentHead() || $schedule->program?->department_id !== $user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!($schedule->isGenerated() || $schedule->isDraft())) {
            return back()->withErrors('Only generated or draft schedules can be finalized.');
        }

        if ($schedule->items->isEmpty()) {
            return back()->withErrors('Cannot finalize empty schedule.');
        }

        if ($schedule->finalize()) {
            // Notify program head
            $programHead = User::where('role', User::ROLE_PROGRAM_HEAD)
                ->where('program_id', $schedule->program_id)
                ->first();

            if ($programHead) {
                $this->notificationService->sendToUser(
                    $programHead,
                    'Schedule Finalized',
                    "The schedule for {$schedule->program->program_name} has been finalized and published.",
                    'success',
                    route('program-head.schedules.show', $schedule)
                );
            }

            return redirect()->route('department-head.schedules.index')
                ->with('success', 'Schedule finalized and published successfully.');
        }

        return back()->withErrors('Failed to finalize schedule.');
    }

    /**
     * Delete a schedule (only drafts or generated).
     */
    public function destroy(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('delete', $schedule);

        // Ensure schedule belongs to department head's department
        if (!$user->isDepartmentHead() || $schedule->program?->department_id !== $user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!($schedule->isDraft() || $schedule->isGenerated())) {
            return back()->withErrors('Only draft or generated schedules can be deleted.');
        }

        try {
            $schedule->delete();

            return redirect()->route('department-head.schedules.index')
                ->with('success', 'Schedule deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors('Failed to delete schedule.');
        }
    }
}
