<?php

namespace App\Http\Controllers\DepartmentHead;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\ScheduleConfiguration;
use App\Models\Semester;
use App\Services\GeneticScheduler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        try {
            Log::debug('Schedule generation request received', [
                'user_id' => $user->id,
                'department_id' => $user->department_id,
            ]);

            $validated = $request->validate([
                'program_id' => 'required|integer|exists:programs,id',
                'academic_year_id' => 'required|integer|exists:academic_years,id',
                'semester' => ['required', 'string', Rule::exists('semesters', 'name')],
                'year_level' => 'required|integer|min:1|max:6',
                'number_of_blocks' => 'required|integer|min:1|max:20',
                'population_size' => 'nullable|integer|min:20|max:500',
                'generations' => 'nullable|integer|min:50|max:500',
                'mutation_rate' => 'nullable|integer|min:1|max:50',
                'crossover_rate' => 'nullable|integer|min:1|max:100',
                'elite_size' => 'nullable|integer|min:1|max:30',
                'stagnation_limit' => 'nullable|integer|min:20|max:200',
            ]);

            Log::debug('Schedule generation validation passed', [
                'program_id' => $validated['program_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'semester' => $validated['semester'],
                'year_level' => $validated['year_level'],
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

            $semesterName = trim((string) $validated['semester']);
            $semesterExists = Semester::query()->where('name', $semesterName)->exists();
            if (!$semesterExists) {
                Log::warning('Invalid semester for generation', [
                    'semester_name' => $semesterName,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid semester selection.',
                ], 422);
            }

            $configuration = ScheduleConfiguration::query()->create([
                'program_id' => (int) $validated['program_id'],
                'academic_year_id' => (int) $validated['academic_year_id'],
                'semester' => $semesterName,
                'year_level' => (int) $validated['year_level'],
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
                        'year_level' => (int) $validated['year_level'],
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

            return response()->json([
                'success' => true,
                'message' => 'Schedules generated successfully using Genetic Algorithm.',
                'data' => [
                    'configuration_id' => $configuration->id,
                    'total_blocks' => (int) $validated['number_of_blocks'],
                    'generated_schedules' => $generatedSchedules,
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
}
