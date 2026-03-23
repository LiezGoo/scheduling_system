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
        // Extend execution time for GA — generation can be CPU-intensive
        set_time_limit(300);

        $generationStartTime = microtime(true);

        try {
            DB::beginTransaction();

            // Extract parameters
            $academicYearId = $parameters['academic_year_id'];
            $semester       = $parameters['semester'];
            $programId      = $parameters['program_id'];
            $yearLevel      = $parameters['year_level'];
            $blockSection   = $parameters['block_section'] ?? 'Block 1';
            $createdBy      = $parameters['created_by'];

            // GA parameters
            $populationSize = $parameters['population_size'] ?? 50;
            $generations    = $parameters['generations'] ?? 100;
            $mutationRate   = ($parameters['mutation_rate'] ?? 15) / 100;
            $crossoverRate  = ($parameters['crossover_rate'] ?? 80) / 100;
            $eliteSize      = $parameters['elite_size'] ?? 5;

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
            $program      = Program::with('department')->findOrFail($programId);

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
                'semester'      => $semester,
                'program_id'    => $programId,
                'year_level'    => $yearLevel,
                'block'         => $blockSection,
                'created_by'    => $createdBy,
                'status'        => Schedule::STATUS_DRAFT,
                'ga_parameters' => [
                    'population_size' => $populationSize,
                    'generations'     => $generations,
                    'mutation_rate'   => $mutationRate * 100,
                    'crossover_rate'  => $crossoverRate * 100,
                    'elite_size'      => $eliteSize,
                ],
            ]);

            Log::info('Schedule generation started', [
                'schedule_id'       => $schedule->id,
                'program'           => $program->program_name,
                'year_level'        => $yearLevel,
                'semester'          => $semester,
                'subjects_count'    => $subjects->count(),
                'instructors_count' => $instructors->count(),
                'rooms_count'       => $rooms->count(),
            ]);

            // Run Genetic Algorithm with progress tracking
            $gaStartTime           = microtime(true);
            $currentGeneration     = 0;
            $bestFitnessProgression = [];

            $progressCallback = function ($currentGen, $totalGen, $fitness) use (&$currentGeneration, &$bestFitnessProgression) {
                $currentGeneration        = $currentGen;
                $bestFitnessProgression[] = $fitness;
                // Progress tracked in-memory only — no DB write per generation
            };

            $bestSolution = $this->gaEngine->evolve(
                $subjects,
                $instructors,
                $rooms,
                $blockSection,
                $yearLevel,
                $semester,
                $academicYearId,
                $programId,
                $progressCallback
            );

            $gaExecutionTime = microtime(true) - $gaStartTime;

            // Save schedule items
            $this->saveScheduleItems($schedule, $bestSolution['genes']);

            // Update schedule with fitness score (keep as DRAFT for review)
            $schedule->update([
                'status'        => Schedule::STATUS_DRAFT,
                'fitness_score' => $bestSolution['fitness'],
            ]);

            $validationReport  = $this->validateGeneratedSchedule($schedule);
            $totalExecutionTime = microtime(true) - $generationStartTime;

            DB::commit();

            Log::info('Schedule generation completed', [
                'schedule_id'       => $schedule->id,
                'fitness_score'     => number_format($bestSolution['fitness'], 4),
                'items_count'       => count($bestSolution['genes']),
                'execution_time'    => number_format($totalExecutionTime, 2) . 's',
                'validation_status' => $validationReport['all_valid'] ? 'VALID' : 'CONFLICTS FOUND',
            ]);

            return [
                'success'      => true,
                'schedule_id'  => $schedule->id,
                'fitness_score'=> $bestSolution['fitness'],
                'schedule'     => $schedule->fresh(['items.subject', 'items.instructor', 'items.room']),
                'genes'        => $bestSolution['genes'],
                'faculty_loads'=> $bestSolution['faculty_loads'],
                'metrics'      => [
                    'execution_time'     => $totalExecutionTime,
                    'ga_execution_time'  => $gaExecutionTime,
                    'actual_generations' => $currentGeneration,
                    'validation'         => $validationReport,
                ],
            ];

        } catch (Exception $e) {
            DB::rollBack();

            $totalExecutionTime = microtime(true) - $generationStartTime;

            Log::error('Schedule generation failed', [
                'error'          => $e->getMessage(),
                'execution_time' => number_format($totalExecutionTime, 2) . 's',
                'trace'          => $e->getTraceAsString(),
            ]);

            if (isset($schedule)) {
                $schedule->update([
                    'status'        => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Load subjects for program, year level, and semester.
     *
     * Uses four progressively-relaxed tiers so that a missing or differently-named
     * pivot column never silently blocks schedule generation.  Each fallback logs a
     * warning so you can identify and fix the underlying data issue.
     *
     * Tier 1 — exact match: program_id + year_level + semester   (ideal)
     * Tier 2 — program_id + year_level                           (semester column absent/different)
     * Tier 3 — program_id only                                   (year_level also absent)
     * Tier 4 — diagnostic log + empty collection                  (nothing found at all)
     */
    protected function loadSubjects(int $programId, int $yearLevel, string $semester): \Illuminate\Support\Collection
    {
        $semesterVariants = $this->getSemesterVariants($semester);

        // ── Tier 1: full match ───────────────────────────────────────────────
        $subjects = Subject::whereHas('programs', function ($q) use ($programId, $yearLevel, $semesterVariants) {
            $q->where('program_id', $programId)
              ->where('year_level', $yearLevel)
              ->whereIn('semester', $semesterVariants);
        })
        ->where('is_active', true)
        ->with('programs')
        ->get();

        if ($subjects->isNotEmpty()) {
            return $subjects;
        }

        Log::warning('loadSubjects Tier 1 returned 0 results — trying Tier 2 (program + year_level)', [
            'program_id' => $programId, 'year_level' => $yearLevel, 'semester' => $semester,
        ]);

        // ── Tier 2: program + year_level (semester may be stored differently) ─
        $subjects = Subject::whereHas('programs', function ($q) use ($programId, $yearLevel) {
            $q->where('program_id', $programId)
              ->where('year_level', $yearLevel);
        })
        ->where('is_active', true)
        ->with('programs')
        ->get();

        if ($subjects->isNotEmpty()) {
            Log::warning('loadSubjects using Tier 2 match (semester column ignored)', [
                'program_id' => $programId, 'year_level' => $yearLevel,
            ]);
            return $subjects;
        }

        Log::warning('loadSubjects Tier 2 returned 0 results — trying Tier 3 (program only)', [
            'program_id' => $programId, 'year_level' => $yearLevel,
        ]);

        // ── Tier 3: program only (year_level may not exist on the pivot) ─────
        $subjects = Subject::whereHas('programs', function ($q) use ($programId) {
            $q->where('program_id', $programId);
        })
        ->where('is_active', true)
        ->with('programs')
        ->get();

        if ($subjects->isNotEmpty()) {
            Log::warning('loadSubjects using Tier 3 match (year_level + semester ignored)', [
                'program_id' => $programId,
            ]);
            return $subjects;
        }

        // ── Tier 4: nothing found — log diagnostic pivot sample ───────────────
        $pivotTable  = 'program_subjects';
        $pivotSample = DB::table($pivotTable)
            ->where('program_id', $programId)
            ->select('program_id', 'subject_id', 'year_level', 'semester')
            ->limit(10)
            ->get();

        $pivotCount = DB::table($pivotTable)->where('program_id', $programId)->count();

        Log::error('loadSubjects: no subjects found at any tier — check pivot data', [
            'program_id'   => $programId,
            'year_level'   => $yearLevel,
            'semester'     => $semester,
            'pivot_table'  => $pivotTable,
            'pivot_count'  => $pivotCount,
            'pivot_sample' => $pivotSample->toArray(),
        ]);

        return collect();
    }

    protected function getSemesterVariants(string $semester): array
    {
        $raw = trim($semester);
        $normalized = strtolower($raw);

        $variants = [$raw];

        if (preg_match('/^(\d+)/', $normalized, $matches)) {
            $number = (int) $matches[1];
            $variants[] = (string) $number;
            $variants[] = "{$number}st";
            $variants[] = "{$number}nd";
            $variants[] = "{$number}rd";
            $variants[] = "{$number}th";

            if ($number === 1) {
                $variants[] = '1st Semester';
                $variants[] = '1st';
                $variants[] = 'First Semester';
                $variants[] = 'first semester';
            } elseif ($number === 2) {
                $variants[] = '2nd Semester';
                $variants[] = '2nd';
                $variants[] = 'Second Semester';
                $variants[] = 'second semester';
            }
        }

        if (str_contains($normalized, 'first')) {
            $variants[] = '1';
            $variants[] = '1st Semester';
            $variants[] = '1st';
        }

        if (str_contains($normalized, 'second')) {
            $variants[] = '2';
            $variants[] = '2nd Semester';
            $variants[] = '2nd';
        }

        return collect($variants)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Load available instructors from department.
     *
     * Tier 1: active instructors WITH daily_scheme configured  (ideal — GA can
     *         respect time-window constraints)
     * Tier 2: all active instructors regardless of scheme      (scheme fields
     *         not filled in — GA will still run, just without time-window limits)
     */
    protected function loadInstructors(int $departmentId): \Illuminate\Support\Collection
    {
        $base = User::where('department_id', $departmentId)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true);

        // Tier 1: instructors with a daily scheme configured
        $instructors = (clone $base)
            ->whereNotNull('daily_scheme_start')
            ->whereNotNull('daily_scheme_end')
            ->get();

        if ($instructors->isNotEmpty()) {
            return $instructors;
        }

        Log::warning('loadInstructors: no instructors with daily_scheme — falling back to all active instructors', [
            'department_id' => $departmentId,
        ]);

        // Tier 2: any active instructor in the department (scheme columns may be null)
        $instructors = $base->get();

        if ($instructors->isEmpty()) {
            Log::error('loadInstructors: no active instructors found at all', [
                'department_id' => $departmentId,
            ]);
        }

        return $instructors;
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
        $schedule->items()->delete();

        $scheduleItems = [];

        foreach ($genes as $gene) {
            $scheduleItems[] = [
                'schedule_id'   => $schedule->id,
                'subject_id'    => $gene['subject_id'],
                'instructor_id' => $gene['instructor_id'],
                'room_id'       => $gene['room_id'],
                'day_of_week'   => $gene['day'],
                'start_time'    => $gene['start_time'],
                'end_time'      => $gene['end_time'],
                'section'       => $gene['section'],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        if (!empty($scheduleItems)) {
            ScheduleItem::insert($scheduleItems);
        }
    }

    /**
     * Validate generated schedule
     */
    public function validateSchedule(Schedule $schedule): array
    {
        $items         = $schedule->items()->with(['subject', 'instructor', 'room'])->get();
        $violations    = [];
        $facultyLoads  = [];

        foreach ($items as $item) {
            $instructorId = $item->instructor_id;
            if (!isset($facultyLoads[$instructorId])) {
                $facultyLoads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }

            $subject  = $item->subject;
            $duration = Carbon::parse($item->start_time)->diffInHours(Carbon::parse($item->end_time), true);

            if ($subject->lecture_hours > 0 && $subject->lab_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration / 2;
                $facultyLoads[$instructorId]['lab']     += $duration / 2;
            } elseif ($subject->lecture_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration;
            } else {
                $facultyLoads[$instructorId]['lab'] += $duration;
            }

            $instructor = $item->instructor;
            if (!$this->validator->isWithinInstructorScheme($instructor, $item->start_time, $item->end_time, $item->day_of_week, $schedule->program_id)) {
                $violations[] = [
                    'type'       => 'scheme_violation',
                    'item_id'    => $item->id,
                    'instructor' => $instructor->first_name . ' ' . $instructor->last_name,
                    'day'        => $item->day_of_week,
                    'time'       => $item->start_time . ' - ' . $item->end_time,
                ];
            }
        }

        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find($instructorId);
            if ($instructor) {
                $loadValidation = $this->validator->validateFacultyLoad($instructor, $loads['lecture'], $loads['lab']);
                if (!$loadValidation['valid']) {
                    $violations[] = [
                        'type'           => 'faculty_overload',
                        'instructor_id'  => $instructorId,
                        'instructor'     => $instructor->first_name . ' ' . $instructor->last_name,
                        'violations'     => $loadValidation['violations'],
                    ];
                }
            }
        }

        return [
            'valid'         => empty($violations),
            'violations'    => $violations,
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
            'schedule_id'   => $schedule->id,
            'status'        => $schedule->status,
            'progress'      => $schedule->ga_progress ?? [],
            'fitness_score' => $schedule->fitness_score,
            'created_at'    => $schedule->created_at,
            'updated_at'    => $schedule->updated_at,
        ];
    }

    /**
     * Comprehensive validation of generated schedule
     */
    public function validateGeneratedSchedule(Schedule $schedule): array
    {
        $items = $schedule->items()->with(['subject', 'instructor', 'room'])->get();

        $report = [
            'room_conflicts'        => 0,
            'instructor_conflicts'  => 0,
            'overload_violations'   => 0,
            'break_violations'      => 0,
            'scheme_violations'     => 0,
            'section_conflicts'     => 0,
            'total_items'           => $items->count(),
            'conflicts_detail'      => [],
            'all_valid'             => true,
        ];

        if ($items->isEmpty()) {
            return $report;
        }

        $facultyLoads = [];

        // 1. Room conflicts
        foreach ($items as $item) {
            $conflicting = $items->where('room_id', $item->room_id)
                ->where('day_of_week', $item->day_of_week)
                ->where('id', '!=', $item->id);

            foreach ($conflicting as $other) {
                if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                    $report['room_conflicts']++;
                    $report['conflicts_detail'][] = [
                        'type'  => 'room_conflict',
                        'room'  => $item->room->room_name,
                        'day'   => $item->day_of_week,
                        'time1' => $item->start_time . '-' . $item->end_time,
                        'time2' => $other->start_time . '-' . $other->end_time,
                    ];
                }
            }
        }

        // 2. Instructor conflicts + load tracking
        foreach ($items as $item) {
            $instructorItems = $items->where('instructor_id', $item->instructor_id)
                ->where('day_of_week', $item->day_of_week)
                ->where('id', '!=', $item->id);

            foreach ($instructorItems as $other) {
                if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                    $report['instructor_conflicts']++;
                    $report['conflicts_detail'][] = [
                        'type'       => 'instructor_conflict',
                        'instructor' => $item->instructor->first_name . ' ' . $item->instructor->last_name,
                        'day'        => $item->day_of_week,
                        'time1'      => $item->start_time . '-' . $item->end_time,
                        'time2'      => $other->start_time . '-' . $other->end_time,
                    ];
                }
            }

            $instructorId = $item->instructor_id;
            if (!isset($facultyLoads[$instructorId])) {
                $facultyLoads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }

            $duration = Carbon::parse($item->start_time)->diffInHours(Carbon::parse($item->end_time), true);
            $subject  = $item->subject;

            if ($subject->lecture_hours > 0 && $subject->lab_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration / 2;
                $facultyLoads[$instructorId]['lab']     += $duration / 2;
            } elseif ($subject->lecture_hours > 0) {
                $facultyLoads[$instructorId]['lecture'] += $duration;
            } else {
                $facultyLoads[$instructorId]['lab'] += $duration;
            }
        }

        // 3. Faculty load violations
        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find($instructorId);
            if ($instructor) {
                $loadValidation = $this->validator->validateFacultyLoad($instructor, $loads['lecture'], $loads['lab']);
                if (!$loadValidation['valid']) {
                    $report['overload_violations']++;
                    $report['conflicts_detail'][] = [
                        'type'       => 'faculty_overload',
                        'instructor' => $instructor->first_name . ' ' . $instructor->last_name,
                        'violations' => $loadValidation['violations'],
                    ];
                }
            }
        }

        // 4. Scheme violations
        foreach ($items as $item) {
            if (!$this->validator->isWithinInstructorScheme($item->instructor, $item->start_time, $item->end_time, $item->day_of_week, $schedule->program_id)) {
                $allowedRange = $this->validator->getAllowedRangeForDay($item->instructor_id, $item->day_of_week, $schedule->program_id);
                $report['scheme_violations']++;
                $report['conflicts_detail'][] = [
                    'type'           => 'scheme_violation',
                    'instructor'     => $item->instructor->first_name . ' ' . $item->instructor->last_name,
                    'day'            => $item->day_of_week,
                    'scheduled_time' => $item->start_time . '-' . $item->end_time,
                    'allowed_range'  => $allowedRange
                        ? ($allowedRange['start'] . '-' . $allowedRange['end'])
                        : 'Not configured',
                ];
            }
        }

        // 5. Break time violations (no more than 4 consecutive hours)
        foreach ($items->groupBy('instructor_id') as $instructorId => $instructorItems) {
            foreach ($instructorItems->groupBy('day_of_week') as $day => $dayItems) {
                $sorted = $dayItems->sortBy('start_time')->values();

                for ($i = 0; $i < $sorted->count() - 1; $i++) {
                    $current    = $sorted[$i];
                    $next       = $sorted[$i + 1];
                    $gapMinutes = Carbon::parse($next->start_time)->diffInMinutes(Carbon::parse($current->end_time));

                    if ($gapMinutes < 60) {
                        $totalHours = Carbon::parse($current->start_time)->diffInHours(Carbon::parse($next->end_time), true);
                        if ($totalHours > 4) {
                            $report['break_violations']++;
                            $report['conflicts_detail'][] = [
                                'type'              => 'break_violation',
                                'instructor'        => $current->instructor->first_name . ' ' . $current->instructor->last_name,
                                'day'               => $day,
                                'consecutive_hours' => round($totalHours, 2),
                            ];
                        }
                    }
                }
            }
        }

        // 6. Section time conflicts
        foreach ($items->groupBy('section') as $section => $sectionItems) {
            foreach ($sectionItems as $item) {
                $conflicts = $sectionItems->where('day_of_week', $item->day_of_week)->where('id', '!=', $item->id);
                foreach ($conflicts as $other) {
                    if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                        $report['section_conflicts']++;
                        $report['conflicts_detail'][] = [
                            'type'    => 'section_conflict',
                            'section' => $section,
                            'day'     => $item->day_of_week,
                            'time1'   => $item->start_time . '-' . $item->end_time,
                            'time2'   => $other->start_time . '-' . $other->end_time,
                        ];
                    }
                }
            }
        }

        $report['all_valid'] = (
            $report['room_conflicts']       === 0 &&
            $report['instructor_conflicts'] === 0 &&
            $report['overload_violations']  === 0 &&
            $report['break_violations']     === 0 &&
            $report['scheme_violations']    === 0 &&
            $report['section_conflicts']    === 0
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