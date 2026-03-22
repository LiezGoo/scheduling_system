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
use App\Models\ScheduleConfiguration;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

    protected function getAllowedSemesterNames(): array
    {
        return Semester::query()
            ->whereNotNull('name')
            ->pluck('name')
            ->push('1st Semester')
            ->push('2nd Semester')
            ->filter()
            ->map(fn ($name) => trim((string) $name))
            ->unique(fn ($name) => strtolower($name))
            ->values()
            ->all();
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

        // Build semester filter options from semester records.
        // If an academic year is selected, scope options to that year.
        $semesterQuery = Semester::query();
        if ($request->filled('academic_year_id')) {
            $semesterQuery->where('academic_year_id', $request->academic_year_id);
        } elseif ($academicYears->isNotEmpty()) {
            $semesterQuery->whereIn('academic_year_id', $academicYears->pluck('id'));
        }

        $semesters = $semesterQuery
            ->whereNotNull('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->toArray();

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

        $programQuery = Program::query()->with('department')->orderBy('program_name');

        if ($user->role === User::ROLE_PROGRAM_HEAD && $user->program_id) {
            $programQuery->where('id', (int) $user->program_id);
        } elseif ($user->role === User::ROLE_ADMIN) {
            // Admin can access all programs.
        } else {
            $programQuery->where('department_id', (int) $user->department_id);
        }

        $programs = $programQuery->get();

        $defaultProgramId = old('program_id');
        if (!$defaultProgramId) {
            $defaultProgramId = $programs->first()?->id;
        }

        $academicYears = AcademicYear::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_year')
            ->get();

        $academicYearIdsWithSemesters = Semester::query()
            ->select('academic_year_id')
            ->whereNotNull('academic_year_id')
            ->distinct()
            ->pluck('academic_year_id');

        $defaultAcademicYearId = (int) (old('academic_year_id')
            ?: $academicYears
                ->first(function ($academicYear) use ($academicYearIdsWithSemesters) {
                    return (bool) $academicYear->is_active
                        && $academicYearIdsWithSemesters->contains($academicYear->id);
                })?->id
            ?: $academicYears
                ->first(function ($academicYear) use ($academicYearIdsWithSemesters) {
                    return $academicYearIdsWithSemesters->contains($academicYear->id);
                })?->id
            ?: $academicYears->first()?->id);

        $semesters = Semester::query()
            ->when($defaultAcademicYearId > 0, function ($query) use ($defaultAcademicYearId) {
                $query->where('academic_year_id', $defaultAcademicYearId);
            })
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [Semester::STATUS_ACTIVE])
            ->orderBy('start_date')
            ->orderBy('name')
            ->get(['id', 'academic_year_id', 'name', 'status']);

        $defaultSemesterId = (int) (old('semester_id')
            ?: $semesters->firstWhere('status', Semester::STATUS_ACTIVE)?->id
            ?: $semesters->first()?->id);

        $yearLevels = YearLevel::query()
            ->where('status', YearLevel::STATUS_ACTIVE)
            ->orderBy('id')
            ->get(['id', 'name', 'code']);

        $defaultYearLevelId = (int) (old('year_level_id') ?: $yearLevels->first()?->id);

        // Get faculty from department
        $faculty = User::where('department_id', $user->department_id)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        // Get all rooms
        $rooms = Room::orderBy('room_code')->get();

        return view('department-head.schedules.generate', compact(
            'programs',
            'academicYears',
            'semesters',
            'defaultAcademicYearId',
            'defaultSemesterId',
            'yearLevels',
            'defaultProgramId',
            'defaultYearLevelId',
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
            'semester' => [
                'required',
                'string',
                Rule::in($this->getAllowedSemesterNames()),
            ],
            'year_level' => 'required|integer|min:1|max:4',
            'number_of_blocks' => 'required|integer|min:1',
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
            // Persist the configuration used for this generation request.
            $configuration = ScheduleConfiguration::create([
                'program_id' => $validated['program_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
                'year_level' => $validated['year_level'],
                'number_of_blocks' => $validated['number_of_blocks'],
                'department_head_id' => $user->id,
            ]);

            $generatedSchedules = [];

            for ($block = 1; $block <= $validated['number_of_blocks']; $block++) {
                $parameters = [
                    'academic_year_id' => $validated['academic_year_id'],
                    'semester' => $validated['semester'],
                    'program_id' => $validated['program_id'],
                    'year_level' => $validated['year_level'],
                    'block_section' => 'Block ' . $block,
                    'created_by' => $user->id,
                    'population_size' => $validated['population_size'] ?? 50,
                    'generations' => $validated['generations'] ?? 100,
                    'mutation_rate' => $validated['mutation_rate'] ?? 15,
                    'crossover_rate' => $validated['crossover_rate'] ?? 80,
                    'elite_size' => $validated['elite_size'] ?? 5,
                ];

                $result = $this->scheduleGenerationService->generateSchedule($parameters);

                if (!$result['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Schedule generation failed for Block ' . $block . ': ' . $result['error'],
                    ], 500);
                }

                $generatedSchedules[] = [
                    'block' => 'Block ' . $block,
                    'schedule_id' => $result['schedule_id'],
                    'fitness_score' => $result['fitness_score'],
                    'items_count' => count($result['genes'] ?? []),
                    'faculty_loads' => $result['faculty_loads'] ?? [],
                    'items' => $result['schedule']->items
                        ->sort(function ($a, $b) {
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            $dayIndexA = array_search($a->day_of_week ?? 'Monday', $days);
                            $dayIndexB = array_search($b->day_of_week ?? 'Monday', $days);

                            if ($dayIndexA !== $dayIndexB) {
                                return $dayIndexA <=> $dayIndexB;
                            }

                            return strcmp((string) $a->getRawOriginal('start_time'), (string) $b->getRawOriginal('start_time'));
                        })
                        ->values()
                        ->map(function ($item) {
                            $subject = $item->subject;
                            $instructor = $item->instructor;
                            $room = $item->room;

                            $roomType = strtolower((string) ($room?->room_type ?? ''));
                            $hasLecture = (float) ($subject?->lecture_hours ?? 0) > 0;
                            $hasLab = (float) ($subject?->lab_hours ?? 0) > 0;

                            $classType = match (true) {
                                $hasLab && !$hasLecture => 'Laboratory',
                                $hasLecture && !$hasLab => 'Lecture',
                                $hasLecture && $hasLab && str_contains($roomType, 'lab') => 'Laboratory',
                                default => 'Lecture',
                            };

                            return [
                                'id' => $item->id,
                                'subject_code' => $subject?->subject_code ?? 'N/A',
                                'subject_name' => $subject?->subject_name ?? 'N/A',
                                'class_type' => $classType,
                                'subject_display' => trim(($subject?->subject_code ?? 'N/A') . ' (' . $classType . ')'),
                                'instructor_name' => $instructor
                                    ? trim(($instructor->first_name ?? '') . ' ' . ($instructor->last_name ?? ''))
                                    : 'TBA',
                                'room_name' => $room?->room_name ?? 'TBA',
                                'room_type' => $room?->room_type ?? 'lecture',
                                'day_of_week' => $item->day_of_week ?? 'Monday',
                                'start_time' => $item->getRawOriginal('start_time') ?? '08:00',
                                'end_time' => $item->getRawOriginal('end_time') ?? '09:00',
                                'duration' => (strtotime($item->getRawOriginal('end_time')) - strtotime($item->getRawOriginal('start_time'))) / 3600,
                                'section' => $item->section ?? 'A',
                            ];
                        })
                        ->toArray(),
                ];
            }

                return response()->json([
                    'success' => true,
                    'message' => 'Schedules generated successfully.',
                    'data' => [
                        'configuration_id' => $configuration->id,
                        'total_blocks' => $validated['number_of_blocks'],
                        'generated_schedules' => $generatedSchedules,
                    ],
                ]);

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
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            abort(403, 'Unauthorized access.');
        }

        if (!($schedule->isGenerated() || $schedule->isDraft())) {
            return $this->finalizeResponse(false, 'Only generated or draft schedules can be finalized.', 422);
        }

        if ($schedule->items->isEmpty()) {
            return $this->finalizeResponse(false, 'Cannot finalize empty schedule.', 422);
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

            return $this->finalizeResponse(
                true,
                'Schedule finalized and published successfully.',
                200,
                route('department-head.schedules.index')
            );
        }

        return $this->finalizeResponse(false, 'Failed to finalize schedule.', 500);
    }

    protected function finalizeResponse(bool $success, string $message, int $status = 200, ?string $redirect = null)
    {
        if (request()->expectsJson()) {
            $payload = [
                'success' => $success,
                'message' => $message,
            ];

            if ($redirect) {
                $payload['redirect'] = $redirect;
            }

            return response()->json($payload, $status);
        }

        if ($success) {
            return redirect()
                ->route('department-head.schedules.index')
                ->with('success', $message);
        }

        return back()->withErrors($message);
    }

    /**
     * Delete a schedule.
     */
    public function destroy(Schedule $schedule)
    {
        $user = Auth::user();

        $this->authorize('delete', $schedule);

        // Ensure schedule belongs to department head's department
        if (!$user->isDepartmentHead() || $schedule->program?->department_id !== $user->department_id) {
            abort(403, 'Unauthorized access.');
        }

        if (!($schedule->isDraft() || $schedule->isGenerated() || $schedule->isPendingApproval() || $schedule->isFinalized())) {
            return back()->withErrors('This schedule cannot be deleted in its current status.');
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