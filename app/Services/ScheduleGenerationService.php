<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Subject;
use App\Models\User;
use App\Models\Room;
use App\Models\AcademicYear;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ScheduleGenerationService
 * 
 * Orchestrates the entire schedule generation process using Genetic Algorithm.
 * Handles data loading, GA execution, and result persistence.
 */
class ScheduleGenerationService
{
    protected GeneticAlgorithmEngine $gaEngine;
    protected ConstraintValidator $validator;

    public function __construct()
    {
        $this->validator = new ConstraintValidator();
    }

    /**
     * Generate schedule using Genetic Algorithm
     */
    public function generateSchedule(array $parameters): array
    {
        $generationStartTime = microtime(true);
        
        try {
            DB::beginTransaction();

            // Extract parameters
            $academicYearId = $parameters['academic_year_id'];
            $semester = $parameters['semester'];
            $programId = $parameters['program_id'];
            $yearLevel = $parameters['year_level'];
            $blockSection = $parameters['block_section'] ?? 'Block 1';
            $createdBy = $parameters['created_by'];

            // GA parameters
            $populationSize = $parameters['population_size'] ?? 50;
            $generations = $parameters['generations'] ?? 100;
            $mutationRate = ($parameters['mutation_rate'] ?? 15) / 100;
            $crossoverRate = ($parameters['crossover_rate'] ?? 80) / 100;
            $eliteSize = $parameters['elite_size'] ?? 5;

            // Initialize GA engine with parameters
            $this->gaEngine = new GeneticAlgorithmEngine(
                $populationSize,
                $generations,
                $mutationRate,
                $crossoverRate,
                $eliteSize
            );

            // Load academic year
            $academicYear = AcademicYear::findOrFail($academicYearId);
            $program = Program::with('department')->findOrFail($programId);

            // Load subjects for this program, year level, and semester
            $subjects = $this->loadSubjects($programId, $yearLevel, $semester);

            if ($subjects->isEmpty()) {
                throw new Exception('No subjects found for the specified program, year level, and semester.');
            }

            // Load available instructors from department
            $instructors = $this->loadInstructors($program->department_id);

            if ($instructors->isEmpty()) {
                throw new Exception('No instructors available in the department.');
            }

            // Load available rooms
            $rooms = $this->loadRooms();

            if ($rooms->isEmpty()) {
                throw new Exception('No rooms available for scheduling.');
            }

            // Create schedule record
            $schedule = Schedule::create([
                'academic_year' => $academicYear->name,
                'semester' => $semester,
                'program_id' => $programId,
                'year_level' => $yearLevel,
                'block' => $blockSection,
                'created_by' => $createdBy,
                'status' => Schedule::STATUS_DRAFT,
                'ga_parameters' => [
                    'population_size' => $populationSize,
                    'generations' => $generations,
                    'mutation_rate' => $mutationRate * 100,
                    'crossover_rate' => $crossoverRate * 100,
                    'elite_size' => $eliteSize,
                ],
            ]);

            Log::info('Schedule generation started', [
                'schedule_id' => $schedule->id,
                'program' => $program->program_name,
                'year_level' => $yearLevel,
                'semester' => $semester,
                'subjects_count' => $subjects->count(),
                'instructors_count' => $instructors->count(),
                'rooms_count' => $rooms->count(),
            ]);

            // === DEBUG LOGGING: Generation Parameters ===
            if (config('app.debug')) {
                Log::debug('GA Engine Configuration', [
                    'population_size' => $populationSize,
                    'generations' => $generations,
                    'mutation_rate' => number_format($mutationRate * 100, 2) . '%',
                    'crossover_rate' => number_format($crossoverRate * 100, 2) . '%',
                    'elite_size' => $eliteSize,
                ]);
            }

            // Run Genetic Algorithm with detailed progress tracking
            $gaStartTime = microtime(true);
            $currentGeneration = 0;
            $bestFitnessProgression = [];

            $progressCallback = function ($currentGen, $totalGen, $fitness) use ($schedule, &$currentGeneration, &$bestFitnessProgression) {
                $currentGeneration = $currentGen;
                $bestFitnessProgression[] = $fitness;

                // Update progress in database
                $schedule->update([
                    'ga_progress' => [
                        'current_generation' => $currentGen,
                        'total_generations' => $totalGen,
                        'best_fitness' => $fitness,
                    ],
                ]);

                // Log every 10 generations
                if (config('app.debug') && $currentGen % 10 === 0) {
                    Log::debug("GA Progress: Generation $currentGen/$totalGen", [
                        'fitness' => number_format($fitness, 4),
                        'improvement' => count($bestFitnessProgression) > 1 
                            ? number_format($bestFitnessProgression[count($bestFitnessProgression) - 1] - $bestFitnessProgression[0], 4)
                            : 'N/A',
                    ]);
                }
            };

            $bestSolution = $this->gaEngine->evolve(
                $subjects,
                $instructors,
                $rooms,
                $blockSection,
                $yearLevel,
                $semester,
                $progressCallback
            );

            $gaEndTime = microtime(true);
            $gaExecutionTime = $gaEndTime - $gaStartTime;

            // Save schedule items
            $this->saveScheduleItems($schedule, $bestSolution['genes']);

            // Update schedule with fitness score (keep as DRAFT for review)
            $schedule->update([
                'status' => Schedule::STATUS_DRAFT,
                'fitness_score' => $bestSolution['fitness'],
            ]);

            // === DEBUG LOGGING: Validation & Metrics ===
            $validationReport = $this->validateGeneratedSchedule($schedule);

            $generationEndTime = microtime(true);
            $totalExecutionTime = $generationEndTime - $generationStartTime;

            if (config('app.debug')) {
                Log::debug('Schedule Generation Metrics', [
                    'ga_execution_time' => number_format($gaExecutionTime, 2) . 's',
                    'total_execution_time' => number_format($totalExecutionTime, 2) . 's',
                    'actual_generations' => $currentGeneration,
                    'items_generated' => count($bestSolution['genes']),
                    'items_per_second' => number_format(count($bestSolution['genes']) / $gaExecutionTime, 2),
                ]);

                Log::debug('Schedule Validation Report', [
                    'room_conflicts' => $validationReport['room_conflicts'],
                    'instructor_conflicts' => $validationReport['instructor_conflicts'],
                    'overload_violations' => $validationReport['overload_violations'],
                    'break_violations' => $validationReport['break_violations'],
                    'scheme_violations' => $validationReport['scheme_violations'],
                    'section_conflicts' => $validationReport['section_conflicts'],
                    'all_valid' => $validationReport['all_valid'] ? 'YES' : 'NO',
                ]);

                Log::debug('Fitness Progression', [
                    'initial_fitness' => $bestFitnessProgression[0] ?? 'N/A',
                    'final_fitness' => $bestSolution['fitness'],
                    'improvement' => isset($bestFitnessProgression[0]) 
                        ? number_format($bestSolution['fitness'] - $bestFitnessProgression[0], 4)
                        : 'N/A',
                    'generations_with_improvement' => count(array_unique($bestFitnessProgression)),
                ]);
            }

            DB::commit();

            Log::info('Schedule generation completed', [
                'schedule_id' => $schedule->id,
                'fitness_score' => number_format($bestSolution['fitness'], 4),
                'items_count' => count($bestSolution['genes']),
                'execution_time' => number_format($totalExecutionTime, 2) . 's',
                'validation_status' => $validationReport['all_valid'] ? 'VALID' : 'CONFLICTS FOUND',
            ]);

            return [
                'success' => true,
                'schedule_id' => $schedule->id,
                'fitness_score' => $bestSolution['fitness'],
                'schedule' => $schedule->fresh(['items.subject', 'items.instructor', 'items.room']),
                'genes' => $bestSolution['genes'],
                'faculty_loads' => $bestSolution['faculty_loads'],
                'metrics' => [
                    'execution_time' => $totalExecutionTime,
                    'ga_execution_time' => $gaExecutionTime,
                    'actual_generations' => $currentGeneration,
                    'validation' => $validationReport,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            $generationEndTime = microtime(true);
            $totalExecutionTime = $generationEndTime - $generationStartTime;

            Log::error('Schedule generation failed', [
                'error' => $e->getMessage(),
                'execution_time' => number_format($totalExecutionTime, 2) . 's',
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($schedule)) {
                $schedule->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Load subjects for program, year level, and semester
     */
    protected function loadSubjects(int $programId, int $yearLevel, string $semester): \Illuminate\Support\Collection
    {
        return Subject::whereHas('programs', function ($query) use ($programId, $yearLevel, $semester) {
            $query->where('program_id', $programId)
                  ->where('year_level', $yearLevel)
                  ->where('semester', $semester);
        })
        ->where('is_active', true)
        ->with('programs')
        ->get();
    }

    /**
     * Load available instructors from department
     */
    protected function loadInstructors(int $departmentId): \Illuminate\Support\Collection
    {
        return User::where('department_id', $departmentId)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true)
            ->whereNotNull('daily_scheme_start')
            ->whereNotNull('daily_scheme_end')
            ->get();
    }

    /**
     * Load available rooms
     */
    protected function loadRooms(): \Illuminate\Support\Collection
    {
        return Room::all();
    }

    /**
     * Save schedule items (genes) to database
     */
    protected function saveScheduleItems(Schedule $schedule, array $genes): void
    {
        // Clear existing items
        $schedule->items()->delete();

        $scheduleItems = [];

        foreach ($genes as $gene) {
            $scheduleItems[] = [
                'schedule_id' => $schedule->id,
                'subject_id' => $gene['subject_id'],
                'instructor_id' => $gene['instructor_id'],
                'room_id' => $gene['room_id'],
                'day_of_week' => $gene['day'],
                'start_time' => $gene['start_time'],
                'end_time' => $gene['end_time'],
                'section' => $gene['section'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Batch insert for performance
        if (!empty($scheduleItems)) {
            ScheduleItem::insert($scheduleItems);
        }
    }

    /**
     * Validate generated schedule
     */
    public function validateSchedule(Schedule $schedule): array
    {
        $items = $schedule->items()
            ->with(['subject', 'instructor', 'room'])
            ->get();

        $violations = [];
        $facultyLoads = [];

        foreach ($items as $item) {
            // Track faculty loads
            $instructorId = $item->instructor_id;
            if (!isset($facultyLoads[$instructorId])) {
                $facultyLoads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }

            // Determine type based on subject
            $subject = $item->subject;
            $duration = Carbon::parse($item->start_time)->diffInHours(Carbon::parse($item->end_time), true);

            if ($subject->lecture_hours > 0 && $subject->lab_hours > 0) {
                // Mixed - assume based on room type or distribute evenly
                $facultyLoads[$instructorId]['lecture'] += $duration / 2;
                $facultyLoads[$instructorId]['lab'] += $duration / 2;
            } elseif ($subject->lecture_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration;
            } else {
                $facultyLoads[$instructorId]['lab'] += $duration;
            }

            // Check scheme violation
            $instructor = $item->instructor;
            if (!$this->validator->isWithinInstructorScheme($instructor, $item->start_time, $item->end_time)) {
                $violations[] = [
                    'type' => 'scheme_violation',
                    'item_id' => $item->id,
                    'instructor' => $instructor->first_name . ' ' . $instructor->last_name,
                    'time' => $item->start_time . ' - ' . $item->end_time,
                ];
            }
        }

        // Check faculty loads
        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find($instructorId);
            if ($instructor) {
                $loadValidation = $this->validator->validateFacultyLoad(
                    $instructor,
                    $loads['lecture'],
                    $loads['lab']
                );

                if (!$loadValidation['valid']) {
                    $violations[] = [
                        'type' => 'faculty_overload',
                        'instructor_id' => $instructorId,
                        'instructor' => $instructor->first_name . ' ' . $instructor->last_name,
                        'violations' => $loadValidation['violations'],
                    ];
                }
            }
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations,
            'faculty_loads' => $facultyLoads,
        ];
    }

    /**
     * Get generation progress
     */
    public function getProgress(int $scheduleId): array
    {
        $schedule = Schedule::findOrFail($scheduleId);

        return [
            'schedule_id' => $schedule->id,
            'status' => $schedule->status,
            'progress' => $schedule->ga_progress ?? [],
            'fitness_score' => $schedule->fitness_score,
            'created_at' => $schedule->created_at,
            'updated_at' => $schedule->updated_at,
        ];
    }

    /**
     * Comprehensive validation of generated schedule
     * Returns detailed conflict report for testing
     * 
     * @param Schedule $schedule
     * @return array {
     *     room_conflicts: int,
     *     instructor_conflicts: int,
     *     overload_violations: int,
     *     break_violations: int,
     *     scheme_violations: int,
     *     section_conflicts: int,
     *     total_items: int,
     *     all_valid: bool
     * }
     */
    public function validateGeneratedSchedule(Schedule $schedule): array
    {
        $items = $schedule->items()
            ->with(['subject', 'instructor', 'room'])
            ->get();

        $report = [
            'room_conflicts' => 0,
            'instructor_conflicts' => 0,
            'overload_violations' => 0,
            'break_violations' => 0,
            'scheme_violations' => 0,
            'section_conflicts' => 0,
            'total_items' => $items->count(),
            'conflicts_detail' => [],
            'all_valid' => true,
        ];

        if ($items->isEmpty()) {
            return $report;
        }

        $facultyLoads = [];
        $itemsArray = $items->toArray();

        // 1. Check room conflicts
        foreach ($items as $index => $item) {
            $conflictingItems = $items->where('room_id', $item->room_id)
                ->where('day_of_week', $item->day_of_week)
                ->where('id', '!=', $item->id);

            foreach ($conflictingItems as $conflicting) {
                if ($this->timesOverlap($item->start_time, $item->end_time, $conflicting->start_time, $conflicting->end_time)) {
                    $report['room_conflicts']++;
                    $report['conflicts_detail'][] = [
                        'type' => 'room_conflict',
                        'room' => $item->room->room_name,
                        'day' => $item->day_of_week,
                        'time1' => $item->start_time . '-' . $item->end_time,
                        'time2' => $conflicting->start_time . '-' . $conflicting->end_time,
                    ];
                }
            }
        }

        // 2. Check instructor time conflicts
        foreach ($items as $item) {
            $instructorItems = $items->where('instructor_id', $item->instructor_id)
                ->where('day_of_week', $item->day_of_week)
                ->where('id', '!=', $item->id);

            foreach ($instructorItems as $other) {
                if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                    $report['instructor_conflicts']++;
                    $report['conflicts_detail'][] = [
                        'type' => 'instructor_conflict',
                        'instructor' => $item->instructor->first_name . ' ' . $item->instructor->last_name,
                        'day' => $item->day_of_week,
                        'time1' => $item->start_time . '-' . $item->end_time,
                        'time2' => $other->start_time . '-' . $other->end_time,
                    ];
                }
            }

            // Track faculty loads
            $instructorId = $item->instructor_id;
            if (!isset($facultyLoads[$instructorId])) {
                $facultyLoads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }

            $duration = Carbon::parse($item->start_time)->diffInHours(Carbon::parse($item->end_time), true);
            $subject = $item->subject;

            if ($subject->lecture_hours > 0 && $subject->lab_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration / 2;
                $facultyLoads[$instructorId]['lab'] += $duration / 2;
            } elseif ($subject->lecture_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration;
            } else {
                $facultyLoads[$instructorId]['lab'] += $duration;
            }
        }

        // 3. Check faculty load violations
        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find($instructorId);
            if ($instructor) {
                $loadValidation = $this->validator->validateFacultyLoad(
                    $instructor,
                    $loads['lecture'],
                    $loads['lab']
                );

                if (!$loadValidation['valid']) {
                    $report['overload_violations']++;
                    $report['conflicts_detail'][] = [
                        'type' => 'faculty_overload',
                        'instructor' => $instructor->first_name . ' ' . $instructor->last_name,
                        'violations' => $loadValidation['violations'],
                    ];
                }
            }
        }

        // 4. Check scheme violations
        foreach ($items as $item) {
            if (!$this->validator->isWithinInstructorScheme($item->instructor, $item->start_time, $item->end_time)) {
                $report['scheme_violations']++;
                $report['conflicts_detail'][] = [
                    'type' => 'scheme_violation',
                    'instructor' => $item->instructor->first_name . ' ' . $item->instructor->last_name,
                    'scheduled_time' => $item->start_time . '-' . $item->end_time,
                    'allowed_range' => $item->instructor->daily_scheme_start . '-' . $item->instructor->daily_scheme_end,
                ];
            }
        }

        // 5. Check break time violations (no more than 4 consecutive hours)
        foreach ($items->groupBy('instructor_id') as $instructorId => $instructorItems) {
            foreach ($instructorItems->groupBy('day_of_week') as $day => $dayItems) {
                $sorted = $dayItems->sortBy('start_time')->values();

                for ($i = 0; $i < $sorted->count() - 1; $i++) {
                    $current = $sorted[$i];
                    $next = $sorted[$i + 1];

                    $currentEnd = Carbon::parse($current->end_time);
                    $nextStart = Carbon::parse($next->start_time);

                    $gapMinutes = $nextStart->diffInMinutes($currentEnd);

                    if ($gapMinutes < 60) {
                        // Check if teaching consecutively
                        $firstStart = Carbon::parse($current->start_time);
                        $lastEnd = Carbon::parse($next->end_time);
                        $totalHours = $firstStart->diffInHours($lastEnd, true);

                        if ($totalHours > 4) {
                            $report['break_violations']++;
                            $report['conflicts_detail'][] = [
                                'type' => 'break_violation',
                                'instructor' => $current->instructor->first_name . ' ' . $current->instructor->last_name,
                                'day' => $day,
                                'consecutive_hours' => round($totalHours, 2),
                            ];
                        }
                    }
                }
            }
        }

        // 6. Check section time conflict (same section can't have 2 subjects at same time)
        foreach ($items->groupBy('section') as $section => $sectionItems) {
            foreach ($sectionItems as $item) {
                $conflicts = $sectionItems
                    ->where('day_of_week', $item->day_of_week)
                    ->where('id', '!=', $item->id);

                foreach ($conflicts as $other) {
                    if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                        $report['section_conflicts']++;
                        $report['conflicts_detail'][] = [
                            'type' => 'section_conflict',
                            'section' => $section,
                            'day' => $item->day_of_week,
                            'time1' => $item->start_time . '-' . $item->end_time,
                            'time2' => $other->start_time . '-' . $other->end_time,
                        ];
                    }
                }
            }
        }

        // Determine overall validity
        $report['all_valid'] = (
            $report['room_conflicts'] === 0 &&
            $report['instructor_conflicts'] === 0 &&
            $report['overload_violations'] === 0 &&
            $report['break_violations'] === 0 &&
            $report['scheme_violations'] === 0 &&
            $report['section_conflicts'] === 0
        );

        return $report;
    }

    /**
     * Check if two time slots overlap
     */
    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $s1 = Carbon::parse($start1);
        $e1 = Carbon::parse($end1);
        $s2 = Carbon::parse($start2);
        $e2 = Carbon::parse($end2);

        return $s1->lessThan($e2) && $s2->lessThan($e1);
    }
}
