<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleConfiguration;
use App\Models\ScheduleItem;
use App\Models\Semester;
use App\Models\YearLevel;
use App\Models\Subject;
use App\Models\InstructorLoad;
use App\Services\GeneticScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GenerateScheduleController extends Controller
{
    public function __construct(private readonly GeneticScheduler $geneticScheduler)
    {
    }

    public function generate(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isDepartmentHead() || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Only Department Heads can generate schedules.',
            ], 403);
        }

        Log::info('Schedule Generation Request', [
            'user_id' => $user->id,
            'department_id' => $user->department_id,
            'payload' => $request->all(),
        ]);

        try {
            Log::debug('Schedule generation request received', [
                'user_id' => $user->id,
                'department_id' => $user->department_id,
            ]);

            $normalizedInput = $request->all();

            if (empty($normalizedInput['semester_id']) && !empty($normalizedInput['semester'])) {
                $semesterId = Semester::query()
                    ->when(!empty($normalizedInput['academic_year_id']), function ($query) use ($normalizedInput) {
                        $query->where('academic_year_id', (int) $normalizedInput['academic_year_id']);
                    })
                    ->where('name', trim((string) $normalizedInput['semester']))
                    ->value('id');

                if ($semesterId) {
                    $normalizedInput['semester_id'] = (int) $semesterId;
                }
            }

            if (empty($normalizedInput['year_level_id']) && !empty($normalizedInput['year_level'])) {
                $yearLevelInput = trim((string) $normalizedInput['year_level']);

                $resolvedYearLevelId = YearLevel::query()
                    ->where(function ($query) use ($yearLevelInput) {
                        $query->where('name', $yearLevelInput)
                            ->orWhere('code', $yearLevelInput);

                        if (ctype_digit($yearLevelInput)) {
                            $query->orWhere('id', (int) $yearLevelInput);
                        }
                    })
                    ->value('id');

                if ($resolvedYearLevelId) {
                    $normalizedInput['year_level_id'] = (int) $resolvedYearLevelId;
                }
            }

            $validator = Validator::make($normalizedInput, [
                'program_id' => 'required|integer|exists:programs,id',
                'academic_year_id' => 'required|integer|exists:academic_years,id',
                'semester_id' => ['required', 'integer', 'exists:semesters,id'],
                'year_level_id' => ['required', 'integer', 'exists:year_levels,id'],
                'number_of_blocks' => 'required|integer|min:1|max:20',
                'population_size' => 'nullable|integer|min:20|max:500',
                'generations' => 'nullable|integer|min:50|max:500',
                'mutation_rate' => 'nullable|integer|min:1|max:50',
                'crossover_rate' => 'nullable|integer|min:1|max:100',
                'elite_size' => 'nullable|integer|min:1|max:30',
                'stagnation_limit' => 'nullable|integer|min:20|max:200',
            ]);

            if ($validator->fails()) {
                Log::error('Schedule generation validation errors', [
                    'errors' => $validator->errors()->toArray(),
                    'payload' => $normalizedInput,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            Log::debug('Schedule generation validation passed', [
                'program_id' => $validated['program_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester_id' => $validated['semester_id'],
                'year_level_id' => $validated['year_level_id'],
                'number_of_blocks' => $validated['number_of_blocks'],
            ]);

            $program = Program::query()->find((int) $validated['program_id']);
            if (!$program || (int) $program->department_id !== (int) $user->department_id) {
                Log::warning('Invalid program for department', [
                    'program_id' => $validated['program_id'],
                    'user_department_id' => $user->department_id,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid program selection for your department.',
                ], 403);
            }

            $semester = Semester::query()
                ->where('id', (int) $validated['semester_id'])
                ->where('academic_year_id', (int) $validated['academic_year_id'])
                ->first();

            if (!$semester) {
                Log::warning('Invalid semester for generation', [
                    'semester_id' => $validated['semester_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid semester selection.',
                ], 422);
            }

            $yearLevel = YearLevel::query()->find((int) $validated['year_level_id']);
            $yearLevelValue = $this->resolveYearLevelValue($yearLevel);

            if ($yearLevelValue === null) {
                Log::warning('Invalid year level for generation', [
                    'year_level_id' => $validated['year_level_id'],
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year level selection.',
                ], 422);
            }

            $semesterName = trim((string) $semester->name);

            // Normalize semester to lowercase to match program_subjects table
            $normalizedSemester = strtolower($semesterName);
            
            Log::debug('Fetching subjects with parameters', [
                'program_id' => $validated['program_id'],
                'year_level' => $yearLevelValue,
                'semester' => $normalizedSemester,
            ]);

            // Use raw join to properly filter by pivot table columns
            $subjects = DB::table('program_subjects')
                ->join('subjects', 'subjects.id', '=', 'program_subjects.subject_id')
                ->where('program_subjects.program_id', (int) $validated['program_id'])
                ->where('program_subjects.year_level', (int) $yearLevelValue)
                ->where('program_subjects.semester', $normalizedSemester)
                ->where('subjects.is_active', true)
                ->select('subjects.id', 'subjects.subject_code')
                ->get();
            
            Log::debug('Subjects fetched', [
                'count' => count($subjects),
                'subjects' => $subjects->pluck('subject_code')->toArray(),
            ]);

            if ($subjects->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects found for the selected program, semester, and year level.',
                ], 422);
            }

            $facultyLoads = InstructorLoad::query()
                ->where('program_id', (int) $validated['program_id'])
                ->where('academic_year_id', (int) $validated['academic_year_id'])
                ->where('semester', $semesterName)
                ->where('year_level', (int) $yearLevelValue)
                ->get(['subject_id']);

            Log::info('Schedule Generation Dataset Counts', [
                'subjects_count' => $subjects->count(),
                'faculty_loads_count' => $facultyLoads->count(),
                'lecture_rooms_count' => Room::query()
                    ->where(function ($query): void {
                        $query->whereNull('room_type')
                            ->orWhere('room_type', 'NOT LIKE', '%lab%');
                    })
                    ->count(),
                'lab_rooms_count' => Room::query()
                    ->where('room_type', 'LIKE', '%lab%')
                    ->count(),
            ]);

            if ($facultyLoads->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No faculty loads assigned for the selected term. Assign faculty loads first.',
                ], 422);
            }

            $assignedSubjectIds = $facultyLoads->pluck('subject_id')->map(fn ($id) => (int) $id)->unique();
            $missingAssignments = $subjects
                ->filter(fn ($subject) => !$assignedSubjectIds->contains((int) $subject->id))
                ->pluck('subject_code')
                ->values();

            if ($missingAssignments->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some subjects have no faculty loads assigned: ' . $missingAssignments->join(', '),
                ], 422);
            }

            $lectureRooms = Room::query()
                ->where(function ($query): void {
                    $query->whereNull('room_type')
                        ->orWhere('room_type', 'NOT LIKE', '%lab%');
                })
                ->count();

            $labRooms = Room::query()
                ->where('room_type', 'LIKE', '%lab%')
                ->count();

            if ($lectureRooms === 0 || $labRooms === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient room inventory. Ensure at least one lecture room and one laboratory room are available.',
                ], 422);
            }

            $configuration = ScheduleConfiguration::query()->create([
                'program_id' => (int) $validated['program_id'],
                'academic_year_id' => (int) $validated['academic_year_id'],
                'semester' => $semesterName,
                'year_level' => (int) $yearLevelValue,
                'number_of_blocks' => (int) $validated['number_of_blocks'],
                'department_head_id' => (int) $user->id,
            ]);

            Log::info('Schedule configuration created', [
                'configuration_id' => $configuration->id,
                'program_id' => $validated['program_id'],
                'number_of_blocks' => $validated['number_of_blocks'],
            ]);

            $generatedSchedules = [];

            for ($block = 1; $block <= (int) $validated['number_of_blocks']; $block++) {
                try {
                    Log::debug('Generating block', [
                        'configuration_id' => $configuration->id,
                        'block' => $block,
                        'total_blocks' => $validated['number_of_blocks'],
                    ]);

                    $result = $this->geneticScheduler->generate([
                        'program_id' => (int) $validated['program_id'],
                        'academic_year_id' => (int) $validated['academic_year_id'],
                        'semester' => $semesterName,
                        'year_level' => (int) $yearLevelValue,
                        'block_section' => 'Block ' . $block,
                        'created_by' => (int) $user->id,
                        'population_size' => (int) ($validated['population_size'] ?? 80),
                        'generations' => (int) ($validated['generations'] ?? 200),
                        'mutation_rate' => (int) ($validated['mutation_rate'] ?? 15),
                        'crossover_rate' => (int) ($validated['crossover_rate'] ?? 80),
                        'elite_size' => (int) ($validated['elite_size'] ?? 5),
                        'stagnation_limit' => (int) ($validated['stagnation_limit'] ?? 60),
                    ]);

                    if (!($result['success'] ?? false)) {
                        Log::error('GA generation failed for block', [
                            'configuration_id' => $configuration->id,
                            'block' => $block,
                            'error_message' => $result['message'] ?? 'Unknown error',
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Generation failed for Block ' . $block . ': ' . ($result['message'] ?? 'Unknown error'),
                        ], 422);
                    }

                    Log::info('Block generation successful', [
                        'configuration_id' => $configuration->id,
                        'block' => $block,
                        'schedule_id' => $result['schedule_id'],
                        'fitness_score' => $result['fitness_score'],
                    ]);

                    $generatedSchedules[] = [
                        'block' => 'Block ' . $block,
                        'schedule_id' => (int) $result['schedule_id'],
                        'fitness_score' => (float) $result['fitness_score'],
                        'timetable' => $this->buildTimetablePayload((int) $result['schedule_id']),
                        'metrics' => $result['metrics'] ?? [],
                        'overloaded_faculty' => array_values(array_filter(
                            $result['faculty_workloads'] ?? [],
                            fn (array $row): bool => (($row['status'] ?? 'Normal') === 'Overloaded')
                        )),
                    ];
                } catch (\Throwable $blockException) {
                    Log::error('Exception during block generation', [
                        'configuration_id' => $configuration->id,
                        'block' => $block,
                        'error' => $blockException->getMessage(),
                        'trace' => $blockException->getTraceAsString(),
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Error generating Block ' . $block . ': ' . $blockException->getMessage(),
                    ], 500);
                }
            }

            Log::info('Schedule generation completed successfully', [
                'configuration_id' => $configuration->id,
                'total_blocks' => count($generatedSchedules),
            ]);

            $primaryTimetable = [];
            foreach ($generatedSchedules as $entry) {
                if (!empty($entry['timetable']) && is_array($entry['timetable'])) {
                    $primaryTimetable = $entry['timetable'];
                    break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Schedules generated successfully using Genetic Algorithm.',
                'timetable' => $primaryTimetable,
                'blocks' => $generatedSchedules,
                'data' => [
                    'configuration_id' => $configuration->id,
                    'total_blocks' => (int) $validated['number_of_blocks'],
                    'generated_schedules' => $generatedSchedules,
                    'timetable' => $primaryTimetable,
                    'blocks' => $generatedSchedules,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $validationException) {
            Log::warning('Validation failed for schedule generation', [
                'errors' => $validationException->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validationException->errors(),
            ], 422);
        } catch (\Exception $exception) {
            Log::error('Schedule Generation Error: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        } catch (\Throwable $exception) {
            Log::error('Unexpected error during schedule generation', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Generation error: ' . $exception->getMessage(),
            ], 500);
        }
    }

    public function progress(int $configuration)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isDepartmentHead() || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $config = ScheduleConfiguration::query()->find($configuration);
        if (!$config || (int) $config->department_head_id !== (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule configuration not found.',
            ], 404);
        }

        $generatedCount = Schedule::query()
            ->where('program_id', (int) $config->program_id)
            ->where('academic_year', optional($config->academicYear)->name)
            ->where('semester', (string) $config->semester)
            ->where('year_level', (int) $config->year_level)
            ->where('created_by', (int) $user->id)
            ->where('created_at', '>=', $config->created_at)
            ->count();

        $total = max(1, (int) $config->number_of_blocks);
        $completed = min($generatedCount, $total);
        $percent = (int) floor(($completed / $total) * 100);

        return response()->json([
            'success' => true,
            'data' => [
                'configuration_id' => (int) $config->id,
                'generated_blocks' => $completed,
                'total_blocks' => $total,
                'progress_percent' => $percent,
                'is_complete' => $completed >= $total,
            ],
        ]);
    }

    public function audit(Schedule $schedule)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (!$user || !$user->isDepartmentHead() || !$user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $schedule->load(['program', 'items.subject', 'items.instructor', 'items.room']);

        if (!$schedule->program || (int) $schedule->program->department_id !== (int) $user->department_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.',
            ], 403);
        }

        $items = $schedule->items;
        $facultyConflicts = 0;
        $roomConflicts = 0;
        $blockConflicts = 0;

        for ($i = 0; $i < $items->count(); $i++) {
            for ($j = $i + 1; $j < $items->count(); $j++) {
                $left = $items[$i];
                $right = $items[$j];

                if ($left->day_of_week !== $right->day_of_week) {
                    continue;
                }

                if (!$this->timesOverlap((string) $left->start_time, (string) $left->end_time, (string) $right->start_time, (string) $right->end_time)) {
                    continue;
                }

                if ((int) $left->instructor_id === (int) $right->instructor_id) {
                    $facultyConflicts++;
                }

                if ((int) $left->room_id === (int) $right->room_id) {
                    $roomConflicts++;
                }

                if ((string) $left->section === (string) $right->section) {
                    $blockConflicts++;
                }
            }
        }

        $facultyDurations = [];
        foreach ($items as $item) {
            $minutes = Carbon::parse((string) $item->start_time)
                ->diffInMinutes(Carbon::parse((string) $item->end_time));
            $facultyDurations[(int) $item->instructor_id] = ($facultyDurations[(int) $item->instructor_id] ?? 0) + $minutes;
        }

        $facultyStatuses = [];
        foreach ($facultyDurations as $instructorId => $minutes) {
            $instructor = $items->firstWhere('instructor_id', $instructorId)?->instructor;
            if (!$instructor) {
                continue;
            }

            $limits = $instructor->getWorkloadLimits();
            $maxLoad = (($limits['max_lecture_hours'] ?? 0) + ($limits['max_lab_hours'] ?? 0));
            $hours = round($minutes / 60, 2);
            $overload = max(0, $hours - $maxLoad);

            $facultyStatuses[] = [
                'instructor_id' => $instructorId,
                'instructor_name' => $instructor->full_name,
                'total_assigned_hours' => $hours,
                'max_load' => $maxLoad,
                'overload_hours' => round($overload, 2),
                'status' => $overload > 0 ? 'Overloaded' : 'Normal',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'schedule_id' => (int) $schedule->id,
                'program' => $schedule->program->program_name,
                'block' => $schedule->block,
                'fitness_score' => (float) ($schedule->fitness_score ?? 0),
                'hard_conflicts' => [
                    'faculty_conflicts' => $facultyConflicts,
                    'room_conflicts' => $roomConflicts,
                    'block_conflicts' => $blockConflicts,
                    'total' => $facultyConflicts + $roomConflicts + $blockConflicts,
                ],
                'faculty_workloads' => $facultyStatuses,
            ],
        ]);
    }

    private function timesOverlap(string $startA, string $endA, string $startB, string $endB): bool
    {
        $sA = strtotime($startA);
        $eA = strtotime($endA);
        $sB = strtotime($startB);
        $eB = strtotime($endB);

        return $sA < $eB && $eA > $sB;
    }

    /**
     * Build normalized timetable rows for frontend grid rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTimetablePayload(int $scheduleId): array
    {
        $dayOrder = [
            'Monday' => 0,
            'Tuesday' => 1,
            'Wednesday' => 2,
            'Thursday' => 3,
            'Friday' => 4,
            'Saturday' => 5,
        ];

        $items = ScheduleItem::query()
            ->with([
                'subject:id,subject_code,subject_name',
                'instructor:id,first_name,middle_name,last_name',
                'room:id,room_code,room_name,room_type',
            ])
            ->where('schedule_id', $scheduleId)
            ->get()
            ->sortBy(function (ScheduleItem $item) use ($dayOrder): string {
                $day = (string) $item->day_of_week;
                $dayIndex = $dayOrder[$day] ?? 99;
                $start = Carbon::parse((string) $item->start_time)->format('H:i');

                return str_pad((string) $dayIndex, 2, '0', STR_PAD_LEFT) . '-' . $start;
            })
            ->values();

        return $items->map(function (ScheduleItem $item): array {
            $subjectCode = (string) ($item->subject?->subject_code ?? 'N/A');
            $subjectName = (string) ($item->subject?->subject_name ?? 'Unknown Subject');

            $faculty = trim((string) ($item->instructor?->full_name ?? ''));
            if ($faculty === '') {
                $faculty = 'TBA';
            }

            $roomCode = trim((string) ($item->room?->room_code ?? ''));
            $roomName = trim((string) ($item->room?->room_name ?? ''));
            $room = $roomCode !== '' ? $roomCode : ($roomName !== '' ? $roomName : 'TBA');

            $startTime = Carbon::parse((string) $item->start_time)->format('H:i');
            $endTime = Carbon::parse((string) $item->end_time)->format('H:i');
            $subjectLabel = $subjectCode !== 'N/A' ? ($subjectCode . ' - ' . $subjectName) : $subjectName;

            return [
                'schedule_id' => (int) $item->schedule_id,
                'day' => (string) $item->day_of_week,
                'time' => $startTime,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'subject' => $subjectLabel,
                'subject_code' => $subjectCode,
                'subject_name' => $subjectName,
                'faculty' => $faculty,
                'room' => $room,
                'class_type' => stripos((string) ($item->room?->room_type ?? ''), 'lab') !== false ? 'lab' : 'lecture',
            ];
        })->all();
    }

    private function resolveYearLevelValue(?YearLevel $yearLevel): ?int
    {
        if (!$yearLevel) {
            return null;
        }

        $code = trim((string) ($yearLevel->code ?? ''));
        if ($code !== '' && ctype_digit($code)) {
            return (int) $code;
        }

        if ($yearLevel->name && preg_match('/\d+/', (string) $yearLevel->name, $matches)) {
            return (int) $matches[0];
        }

        return $yearLevel->id ? (int) $yearLevel->id : null;
    }
}
