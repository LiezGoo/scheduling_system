<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\InstructorLoad;
use App\Models\Program;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeneticScheduler
{
    private const BASE_FITNESS = 10000;
    private const HARD_CONFLICT_WEIGHT = 1000;
    private const OVERLOAD_HOUR_WEIGHT = 200;
    private const SOFT_PENALTY_WEIGHT = 50;
    private const NSTP_VIOLATION_WEIGHT = 2000;
    private const BREAK_TIME_VIOLATION_WEIGHT = 500;
    private const NON_NSTP_SATURDAY_WEIGHT = 800;
    private const OVERFLOW_SATURDAY_WEIGHT = 200;

    private const WORKING_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    private const DEFAULT_DAY_START = '07:00';
    private const DEFAULT_DAY_END = '19:00';
    private const TIME_SLOT_STEP_MINUTES = 30;

    // Break time hours that must remain free globally (no classes during these times)
    private const BREAK_TIMES = [
        ['start' => '10:00', 'end' => '11:00'],
        ['start' => '11:00', 'end' => '12:00'],
        ['start' => '13:00', 'end' => '14:00'],
        ['start' => '14:00', 'end' => '15:00'],
    ];

    // NSTP subjects must be exactly 3 hours and scheduled only on Saturday
    private const NSTP_REQUIRED_DURATION_MINUTES = 180;
    private const NSTP_REQUIRED_DAY = 'Saturday';

    /**
     * Execute full GA workflow and persist the best schedule.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public function generate(array $parameters): array
    {
        $populationSize = (int) ($parameters['population_size'] ?? 80);
        $generations = (int) ($parameters['generations'] ?? 200);
        $mutationRate = ((int) ($parameters['mutation_rate'] ?? 15)) / 100;
        $crossoverRate = ((int) ($parameters['crossover_rate'] ?? 80)) / 100;
        $eliteSize = max(1, (int) ($parameters['elite_size'] ?? 5));
        $stagnationLimit = (int) ($parameters['stagnation_limit'] ?? 60);

        $programId = (int) $parameters['program_id'];
        $academicYearId = (int) $parameters['academic_year_id'];
        $semester = (string) $parameters['semester'];
        $yearLevel = (int) $parameters['year_level'];
        $blockSection = (string) $parameters['block_section'];
        $createdBy = (int) $parameters['created_by'];

        Log::info('GA Start', [
            'program_id' => $programId,
            'academic_year_id' => $academicYearId,
            'semester' => $semester,
            'year_level' => $yearLevel,
            'block_section' => $blockSection,
            'population_size' => $populationSize,
            'generations' => $generations,
        ]);

        $program = Program::query()->findOrFail($programId);
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        $subjects = $this->loadSubjects($programId, $yearLevel, $semester);
        Log::info('Subjects Count', ['count' => $subjects->count()]);
        if ($subjects->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No subjects found for the selected curriculum context.',
            ];
        }

        $sessions = $this->buildSessionsFromSubjects($subjects, $blockSection);
        if (empty($sessions)) {
            return [
                'success' => false,
                'message' => 'Subjects do not have schedulable lecture/lab hours.',
            ];
        }

        $facultyMap = $this->buildFacultyMap(
            $programId,
            $academicYearId,
            $semester,
            $yearLevel,
            $blockSection
        );
        Log::info('Faculty Count', [
            'count' => count(array_unique(array_merge(...array_values($facultyMap ?: [[]])))),
        ]);

        $missingFacultySubjects = $this->findSubjectsWithNoFaculty($subjects, $facultyMap);
        if (!empty($missingFacultySubjects)) {
            return [
                'success' => false,
                'message' => 'Some subjects have no faculty assignment in faculty_load for this term/block: ' . implode(', ', $missingFacultySubjects),
            ];
        }

        $roomsByType = $this->loadRoomsByType();
        Log::info('Rooms Count', [
            'lecture' => $roomsByType['lecture']->count(),
            'lab' => $roomsByType['lab']->count(),
        ]);
        if ($roomsByType['lecture']->isEmpty() || $roomsByType['lab']->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Insufficient room inventory. Ensure both lecture and laboratory rooms are available.',
            ];
        }

        $durationSet = array_values(array_unique(array_map(
            fn (array $session): int => (int) $session['duration_minutes'],
            $sessions
        )));
        $timeSlotsByDuration = $this->buildTimeSlotsByDuration($durationSet);

        $context = [
            'subjects' => $subjects,
            'sessions' => $sessions,
            'faculty_map' => $facultyMap,
            'rooms_by_type' => $roomsByType,
            'time_slots' => $timeSlotsByDuration,
            'semester' => $semester,
            'year_level' => $yearLevel,
            'block_section' => $blockSection,
            'academic_year_id' => $academicYearId,
            'program_id' => $programId,
            'program' => $program,
            'academic_year' => $academicYear,
        ];

        $population = $this->initializePopulation($populationSize, $context);
        if (empty($population)) {
            return [
                'success' => false,
                'message' => 'Failed to initialize GA population.',
            ];
        }

        $bestChromosome = null;
        $bestFitness = -INF;
        $noImprovement = 0;

        for ($generation = 0; $generation < $generations; $generation++) {
            foreach ($population as &$chromosome) {
                $fitness = $this->calculateFitness($chromosome, $context);
                $chromosome['fitness'] = $fitness['score'];
                $chromosome['metrics'] = $fitness['metrics'];
            }
            unset($chromosome);

            usort($population, fn (array $a, array $b): int => ($b['fitness'] <=> $a['fitness']));

            if ($population[0]['fitness'] > $bestFitness) {
                $bestFitness = $population[0]['fitness'];
                $bestChromosome = $population[0];
                $noImprovement = 0;
            } else {
                $noImprovement++;
            }

            if ($bestChromosome && $this->validate($bestChromosome, $context)['valid']) {
                break;
            }

            if ($noImprovement >= $stagnationLimit) {
                break;
            }

            $nextPopulation = array_slice($population, 0, min($eliteSize, count($population)));

            while (count($nextPopulation) < $populationSize) {
                [$parentA, $parentB] = $this->selectParents($population);

                if (mt_rand(1, 10000) / 10000 <= $crossoverRate) {
                    $child = $this->crossover($parentA, $parentB, $context);
                } else {
                    $child = [
                        'genes' => $this->repairChromosome($parentA['genes'], $context),
                        'fitness' => 0,
                        'metrics' => [],
                    ];
                }

                $this->mutate($child, $mutationRate, $context);
                $nextPopulation[] = $child;
            }

            $population = $nextPopulation;
        }

        if (!$bestChromosome) {
            return [
                'success' => false,
                'message' => 'No solution produced by genetic scheduler.',
            ];
        }

        $validation = $this->validate($bestChromosome, $context);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => 'Best schedule still violates hard constraints: ' . implode('; ', $validation['errors']),
            ];
        }

        return DB::transaction(function () use ($bestChromosome, $context, $createdBy, $parameters): array {
            /** @var Program $program */
            $program = $context['program'];
            /** @var AcademicYear $academicYear */
            $academicYear = $context['academic_year'];

            $schedule = Schedule::query()->create([
                'academic_year' => $academicYear->name,
                'semester' => (string) $context['semester'],
                'program_id' => (int) $context['program_id'],
                'year_level' => (int) $context['year_level'],
                'block' => (string) $context['block_section'],
                'created_by' => $createdBy,
                'status' => Schedule::STATUS_DRAFT,
                'fitness_score' => round((float) $bestChromosome['fitness'], 2),
                'ga_parameters' => [
                    'population_size' => (int) ($parameters['population_size'] ?? 80),
                    'generations' => (int) ($parameters['generations'] ?? 200),
                    'mutation_rate' => (int) ($parameters['mutation_rate'] ?? 15),
                    'crossover_rate' => (int) ($parameters['crossover_rate'] ?? 80),
                    'elite_size' => (int) ($parameters['elite_size'] ?? 5),
                    'fitness_formula' => '10000 - (base_hard_conflicts*1000) - (nstp_violations*2000) - (break_time_conflicts*500) - (non_nstp_saturday_violations*800) - (overflow_saturday_assignments*200) - (overload_hours*200) - (soft_penalties*50)',
                ],
            ]);

            $rows = array_map(function (array $gene) use ($schedule): array {
                return [
                    'schedule_id' => $schedule->id,
                    'subject_id' => (int) $gene['subject_id'],
                    'instructor_id' => (int) $gene['faculty_id'],
                    'room_id' => (int) $gene['room_id'],
                    'day_of_week' => (string) $gene['day'],
                    'start_time' => (string) $gene['start_time'],
                    'end_time' => (string) $gene['end_time'],
                    'section' => (string) $gene['block'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $bestChromosome['genes']);

            ScheduleItem::query()->insert($rows);

            $result = [
                'success' => true,
                'schedule_id' => $schedule->id,
                'program' => $program->program_name,
                'block' => (string) $context['block_section'],
                'fitness_score' => (float) $bestChromosome['fitness'],
                'metrics' => $bestChromosome['metrics'] ?? [],
                'genes' => $bestChromosome['genes'],
                'faculty_workloads' => $this->buildFacultyWorkloadReport($bestChromosome['genes']),
            ];

            Log::info('GA Completed', [
                'schedule_id' => $schedule->id,
                'fitness_score' => $result['fitness_score'],
                'hard_conflicts' => $result['metrics']['hard_conflicts'] ?? null,
            ]);

            return $result;
        });
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function initializePopulation(int $size, array $context): array
    {
        $population = [];

        for ($i = 0; $i < $size; $i++) {
            $population[] = [
                'genes' => $this->buildRandomChromosomeGenes($context),
                'fitness' => 0,
                'metrics' => [],
            ];
        }

        return $population;
    }

    /**
     * @param array<string, mixed> $chromosome
     * @param array<string, mixed> $context
     * @return array{score:float,metrics:array<string,mixed>}
     */
    public function calculateFitness(array $chromosome, array $context): array
    {
        $analysis = $this->analyzeGenes($chromosome['genes'], $context);

        $hardConflicts = (int) $analysis['hard_conflicts'];
        $nstpViolations = (int) ($analysis['nstp_violations'] ?? 0);
        $breakTimeConflicts = (int) ($analysis['break_time_conflicts'] ?? 0);
        $baseHardConflicts = max(0, $hardConflicts - $nstpViolations - $breakTimeConflicts);
        $nonNstpSaturdayViolations = (int) ($analysis['non_nstp_saturday_violations'] ?? 0);
        $overflowSaturdayAssignments = (int) ($analysis['overflow_saturday_assignments'] ?? 0);
        $overloadHours = (float) $analysis['overload_hours'];
        $softPenalties = (int) $analysis['soft_penalties'];

        $fitness = self::BASE_FITNESS
            - ($baseHardConflicts * self::HARD_CONFLICT_WEIGHT)
            - ($nstpViolations * self::NSTP_VIOLATION_WEIGHT)
            - ($breakTimeConflicts * self::BREAK_TIME_VIOLATION_WEIGHT)
            - ($nonNstpSaturdayViolations * self::NON_NSTP_SATURDAY_WEIGHT)
            - ($overflowSaturdayAssignments * self::OVERFLOW_SATURDAY_WEIGHT)
            - ($overloadHours * self::OVERLOAD_HOUR_WEIGHT)
            - ($softPenalties * self::SOFT_PENALTY_WEIGHT);

        return [
            'score' => max(0.0, (float) $fitness),
            'metrics' => $analysis,
        ];
    }

    /**
     * Tournament selection.
     *
     * @param array<int, array<string, mixed>> $population
     * @return array{0:array<string,mixed>,1:array<string,mixed>}
     */
    public function selectParents(array $population): array
    {
        return [
            $this->selectOneParent($population),
            $this->selectOneParent($population),
        ];
    }

    /**
     * @param array<string, mixed> $parentA
     * @param array<string, mixed> $parentB
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function crossover(array $parentA, array $parentB, array $context): array
    {
        $genesA = $parentA['genes'];
        $genesB = $parentB['genes'];

        $size = count($genesA);
        if ($size <= 1 || $size !== count($genesB)) {
            return [
                'genes' => $this->buildRandomChromosomeGenes($context),
                'fitness' => 0,
                'metrics' => [],
            ];
        }

        $point = random_int(1, $size - 1);

        $childGenes = array_merge(
            array_slice($genesA, 0, $point),
            array_slice($genesB, $point)
        );

        return [
            'genes' => $this->repairChromosome($childGenes, $context),
            'fitness' => 0,
            'metrics' => [],
        ];
    }

    /**
     * @param array<string, mixed> $chromosome
     * @param array<string, mixed> $context
     */
    public function mutate(array &$chromosome, float $mutationRate, array $context): void
    {
        $genes = $chromosome['genes'];

        foreach ($genes as $index => $gene) {
            if (mt_rand(1, 10000) / 10000 > $mutationRate) {
                continue;
            }

            $isNstp = (bool) ($gene['is_nstp'] ?? false);
            $mutationType = $isNstp ? 2 : random_int(1, 3); // For NSTP, avoid time slot mutation

            if ($mutationType === 1) {
                // Time slot mutation: for NSTP, force Saturday slot
                if ($isNstp) {
                    $slot = $this->pickRandomTimeSlotForDay(
                        (int) $gene['duration_minutes'],
                        $context['time_slots'],
                        self::NSTP_REQUIRED_DAY
                    );
                    $genes[$index]['overflow_saturday'] = false;
                } else {
                    $slot = $this->pickRandomTimeSlotWithDayPriority(
                        (int) $gene['duration_minutes'],
                        $context['time_slots'],
                        $this->weekdayDays(),
                        [self::NSTP_REQUIRED_DAY]
                    );
                    $genes[$index]['overflow_saturday'] = ((string) ($slot['day'] ?? '') === self::NSTP_REQUIRED_DAY);
                }
                $genes[$index]['day'] = $slot['day'];
                $genes[$index]['start_time'] = $slot['start'];
                $genes[$index]['end_time'] = $slot['end'];
            } elseif ($mutationType === 2) {
                $roomPool = $context['rooms_by_type'][$gene['class_type']] ?? collect();
                if ($roomPool instanceof Collection && $roomPool->isNotEmpty()) {
                    $genes[$index]['room_id'] = (int) $roomPool->random()->id;
                }
            } else {
                $subjectId = (int) $gene['subject_id'];
                $facultyCandidates = $context['faculty_map'][$subjectId] ?? [];
                if (!empty($facultyCandidates)) {
                    $genes[$index]['faculty_id'] = (int) $facultyCandidates[array_rand($facultyCandidates)];
                }
            }
        }

        $chromosome['genes'] = $this->repairChromosome($genes, $context);
    }

    /**
     * Validate hard constraints only.
     *
     * @param array<string, mixed> $chromosome
     * @param array<string, mixed> $context
     * @return array{valid:bool,errors:array<int,string>}
     */
    public function validate(array $chromosome, array $context): array
    {
        $analysis = $this->analyzeGenes($chromosome['genes'], $context);
        $errors = [];

        if ((int) $analysis['invalid_faculty_assignment'] > 0) {
            $errors[] = 'Invalid faculty-subject assignments detected.';
        }

        if ((int) $analysis['invalid_room_type'] > 0) {
            $errors[] = 'Invalid room type assignments detected.';
        }

        if ((int) $analysis['faculty_conflicts'] > 0) {
            $errors[] = 'Faculty time conflicts detected.';
        }

        if ((int) $analysis['room_conflicts'] > 0) {
            $errors[] = 'Room conflicts detected.';
        }

        if ((int) $analysis['block_conflicts'] > 0) {
            $errors[] = 'Block/section time conflicts detected.';
        }

        if ((int) $analysis['nstp_violations'] > 0) {
            $errors[] = 'NSTP scheduling constraints violated (must be Saturday and exactly 3 hours).';
        }

        if ((int) $analysis['break_time_conflicts'] > 0) {
            $errors[] = 'Classes scheduled during mandatory break times.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private function loadSubjects(int $programId, int $yearLevel, string $semester): Collection
    {
        $normalizedSemester = strtolower(trim($semester));

        return Subject::query()
            ->where('is_active', true)
            ->whereHas('programs', function ($query) use ($programId, $yearLevel, $normalizedSemester): void {
                $query->where('program_id', $programId)
                    ->where('year_level', $yearLevel)
                    ->whereRaw('LOWER(semester) = ?', [$normalizedSemester]);
            })
            ->orderBy('subject_code')
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildSessionsFromSubjects(Collection $subjects, string $blockSection): array
    {
        $sessions = [];

        foreach ($subjects as $subject) {
            // Special handling for NSTP subjects: force 3-hour duration on Saturday only
            if ($this->isNstpSubject($subject)) {
                // NSTP must be exactly 180 minutes (3 hours)
                $sessions[] = [
                    'subject_id' => (int) $subject->id,
                    'class_type' => 'lecture',
                    'duration_minutes' => self::NSTP_REQUIRED_DURATION_MINUTES,
                    'block' => $blockSection,
                    'is_nstp' => true,
                ];
            } else {
                // Normal subjects use their lecture and lab hours
                $lectureMinutes = (int) round((float) $subject->lecture_hours * 60);
                $labMinutes = (int) round((float) $subject->lab_hours * 60);

                foreach ($this->splitIntoDurations($lectureMinutes, 60) as $duration) {
                    $sessions[] = [
                        'subject_id' => (int) $subject->id,
                        'class_type' => 'lecture',
                        'duration_minutes' => $duration,
                        'block' => $blockSection,
                        'is_nstp' => false,
                    ];
                }

                foreach ($this->splitIntoDurations($labMinutes, 180) as $duration) {
                    $sessions[] = [
                        'subject_id' => (int) $subject->id,
                        'class_type' => 'lab',
                        'duration_minutes' => $duration,
                        'block' => $blockSection,
                        'is_nstp' => false,
                    ];
                }
            }
        }

        return $sessions;
    }

    private function isNstpSubject(Subject $subject): bool
    {
        if ((bool) $subject->is_nstp) {
            return true;
        }

        $subjectType = strtolower(trim((string) ($subject->subject_type ?? '')));
        if ($subjectType === 'nstp') {
            return true;
        }

        $subjectName = strtolower((string) ($subject->subject_name ?? ''));
        $subjectCode = strtolower((string) ($subject->subject_code ?? ''));

        return str_contains($subjectName, 'nstp') || str_contains($subjectCode, 'nstp');
    }

    /**
     * @return array<int, int>
     */
    private function splitIntoDurations(int $minutes, int $preferredChunk): array
    {
        if ($minutes <= 0) {
            return [];
        }

        $durations = [];
        $remaining = $minutes;

        while ($remaining > 0) {
            if ($remaining >= $preferredChunk) {
                $durations[] = $preferredChunk;
                $remaining -= $preferredChunk;
                continue;
            }

            // Keep slot boundaries aligned to 30 minutes.
            $chunk = max(30, (int) (ceil($remaining / 30) * 30));
            $durations[] = $chunk;
            $remaining -= $chunk;
        }

        return $durations;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private function buildFacultyMap(
        int $programId,
        int $academicYearId,
        string $semester,
        int $yearLevel,
        string $blockSection
    ): array {
        $baseQuery = InstructorLoad::query()
            ->where('program_id', $programId)
            ->where('academic_year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('year_level', $yearLevel);

        $blockLoads = (clone $baseQuery)
            ->where('block_section', $blockSection)
            ->get(['subject_id', 'instructor_id']);

        $loads = $blockLoads->isNotEmpty()
            ? $blockLoads
            : $baseQuery->get(['subject_id', 'instructor_id']);

        $map = [];
        foreach ($loads as $load) {
            $subjectId = (int) $load->subject_id;
            $instructorId = (int) $load->instructor_id;

            if (!isset($map[$subjectId])) {
                $map[$subjectId] = [];
            }

            if (!in_array($instructorId, $map[$subjectId], true)) {
                $map[$subjectId][] = $instructorId;
            }
        }

        return $map;
    }

    /**
     * @return array<int, string>
     */
    private function findSubjectsWithNoFaculty(Collection $subjects, array $facultyMap): array
    {
        $missing = [];

        foreach ($subjects as $subject) {
            if (empty($facultyMap[(int) $subject->id] ?? [])) {
                $missing[] = (string) $subject->subject_code;
            }
        }

        return $missing;
    }

    /**
     * @return array{lecture:Collection<int,Room>,lab:Collection<int,Room>}
     */
    private function loadRoomsByType(): array
    {
        $rooms = Room::query()->orderBy('room_code')->get();

        $lectureRooms = $rooms->filter(function (Room $room): bool {
            return stripos((string) $room->room_type, 'lab') === false;
        })->values();

        $labRooms = $rooms->filter(function (Room $room): bool {
            return stripos((string) $room->room_type, 'lab') !== false;
        })->values();

        return [
            'lecture' => $lectureRooms,
            'lab' => $labRooms,
        ];
    }

    /**
     * @param array<int, int> $durations
     * @return array<int, array<int, array<string, string>>>
     */
    private function buildTimeSlotsByDuration(array $durations): array
    {
        $map = [];

        foreach ($durations as $duration) {
            $map[$duration] = [];
            foreach (self::WORKING_DAYS as $day) {
                $map[$duration] = array_merge($map[$duration], $this->buildDaySlots($day, $duration));
            }
        }

        return $map;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildDaySlots(string $day, int $durationMinutes): array
    {
        $slots = [];

        $cursor = strtotime(self::DEFAULT_DAY_START);
        $end = strtotime(self::DEFAULT_DAY_END);

        while (($cursor + ($durationMinutes * 60)) <= $end) {
            $slotEnd = $cursor + ($durationMinutes * 60);
            
            // Check if this slot overlaps with any break time
            if (!$this->slotOverlapsWithBreakTime(date('H:i', $cursor), date('H:i', $slotEnd))) {
                $slots[] = [
                    'day' => $day,
                    'start' => date('H:i', $cursor),
                    'end' => date('H:i', $slotEnd),
                ];
            }

            $cursor += self::TIME_SLOT_STEP_MINUTES * 60;
        }

        return $slots;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function buildRandomChromosomeGenes(array $context): array
    {
        $genes = [];

        foreach ($context['sessions'] as $session) {
            $gene = $this->buildRandomGeneForSession($session, $context, $genes);
            $genes[] = $gene;
        }

        return $genes;
    }

    /**
     * @param array<string, mixed> $session
     * @param array<string, mixed> $context
     * @param array<int, array<string, mixed>> $existingGenes
     * @return array<string, mixed>
     */
    private function buildRandomGeneForSession(array $session, array $context, array $existingGenes): array
    {
        $subjectId = (int) $session['subject_id'];
        $classType = (string) $session['class_type'];
        $duration = (int) $session['duration_minutes'];
        $isNstp = (bool) ($session['is_nstp'] ?? false);

        $facultyCandidates = $context['faculty_map'][$subjectId] ?? [];
        $roomPool = $context['rooms_by_type'][$classType] ?? collect();

        $attempts = 60;
        while ($attempts-- > 0) {
            $facultyId = (int) $facultyCandidates[array_rand($facultyCandidates)];
            /** @var Room $room */
            $room = $roomPool->random();
            $overflowSaturday = false;
            
            // For NSTP subjects, force Saturday and 3-hour duration
            if ($isNstp) {
                $slot = $this->pickRandomTimeSlotForDay(
                    $duration,
                    $context['time_slots'],
                    self::NSTP_REQUIRED_DAY
                );
            } else {
                $slot = $this->findConflictFreeSlotForGene(
                    $duration,
                    $context['time_slots'],
                    $this->weekdayDays(),
                    $existingGenes,
                    $facultyId,
                    (int) $room->id,
                    (string) $session['block']
                );

                if ($slot === null) {
                    $slot = $this->findConflictFreeSlotForGene(
                        $duration,
                        $context['time_slots'],
                        [self::NSTP_REQUIRED_DAY],
                        $existingGenes,
                        $facultyId,
                        (int) $room->id,
                        (string) $session['block']
                    );
                    $overflowSaturday = $slot !== null;
                }

                if ($slot === null) {
                    $slot = $this->pickRandomTimeSlotWithDayPriority(
                        $duration,
                        $context['time_slots'],
                        $this->weekdayDays(),
                        [self::NSTP_REQUIRED_DAY]
                    );
                    $overflowSaturday = ((string) ($slot['day'] ?? '') === self::NSTP_REQUIRED_DAY);
                }
            }

            $gene = [
                'subject_id' => $subjectId,
                'faculty_id' => $facultyId,
                'room_id' => (int) $room->id,
                'day' => $slot['day'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
                'block' => (string) $session['block'],
                'class_type' => $classType,
                'duration_minutes' => $duration,
                'is_nstp' => $isNstp,
                'overflow_saturday' => !$isNstp && $overflowSaturday,
            ];

            if (!$this->hasImmediateHardConflict($gene, $existingGenes)) {
                return $gene;
            }
        }

        // If no conflict-free placement is found quickly, still return a gene
        // so GA can repair/penalize it instead of stalling.
        if ($isNstp) {
            $fallbackSlot = $this->pickRandomTimeSlotForDay($duration, $context['time_slots'], self::NSTP_REQUIRED_DAY);
        } else {
            $fallbackSlot = $this->pickRandomTimeSlotWithDayPriority(
                $duration,
                $context['time_slots'],
                $this->weekdayDays(),
                [self::NSTP_REQUIRED_DAY]
            );
        }

        $fallbackGene = [
            'subject_id' => $subjectId,
            'faculty_id' => (int) $facultyCandidates[array_rand($facultyCandidates)],
            'room_id' => (int) $roomPool->random()->id,
            'day' => $fallbackSlot['day'],
            'start_time' => $fallbackSlot['start'],
            'end_time' => $fallbackSlot['end'],
            'block' => (string) $session['block'],
            'class_type' => $classType,
            'duration_minutes' => $duration,
            'is_nstp' => $isNstp,
            'overflow_saturday' => !$isNstp && ((string) ($fallbackSlot['day'] ?? '') === self::NSTP_REQUIRED_DAY),
        ];

        // Ensure fallback gene respects NSTP constraints
        if ($isNstp) {
            if ($fallbackGene['day'] !== self::NSTP_REQUIRED_DAY) {
                $fallbackGene['day'] = self::NSTP_REQUIRED_DAY;
            }
            if ($fallbackGene['duration_minutes'] !== self::NSTP_REQUIRED_DURATION_MINUTES) {
                $fallbackGene['duration_minutes'] = self::NSTP_REQUIRED_DURATION_MINUTES;
                $startTime = strtotime((string) $fallbackGene['start_time']);
                $fallbackGene['end_time'] = date('H:i', $startTime + (self::NSTP_REQUIRED_DURATION_MINUTES * 60));
            }
        }

        return $fallbackGene;
    }

    /**
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @return array<string, string>
     */
    private function pickRandomTimeSlot(int $duration, array $timeSlotMap): array
    {
        $slots = $timeSlotMap[$duration] ?? [];

        if (empty($slots)) {
            // Try to find slots for any available duration
            foreach ($timeSlotMap as $availableSlots) {
                if (!empty($availableSlots)) {
                    $slots = $availableSlots;
                    break;
                }
            }
        }

        if (empty($slots)) {
            // Last resort: generate a safe slot that doesn't overlap with break times
            return $this->generateSafeTimeSlot($duration);
        }

        /** @var array<string, string> $slot */
        $slot = $slots[array_rand($slots)];

        return $slot;
    }

    /**
     * Pick a random slot using prioritized day groups.
     *
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @param array<int, string> $preferredDays
     * @param array<int, string> $fallbackDays
     * @return array<string, string>
     */
    private function pickRandomTimeSlotWithDayPriority(
        int $duration,
        array $timeSlotMap,
        array $preferredDays,
        array $fallbackDays = []
    ): array {
        $preferredSlots = $this->collectSlotsForDays($duration, $timeSlotMap, $preferredDays);
        if (!empty($preferredSlots)) {
            return $preferredSlots[array_rand($preferredSlots)];
        }

        $fallbackSlots = $this->collectSlotsForDays($duration, $timeSlotMap, $fallbackDays);
        if (!empty($fallbackSlots)) {
            return $fallbackSlots[array_rand($fallbackSlots)];
        }

        $fallbackDay = $preferredDays[0] ?? ($fallbackDays[0] ?? self::WORKING_DAYS[0]);
        return $this->generateSafeTimeSlotForDay($duration, $fallbackDay);
    }

    /**
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @param array<int, string> $days
     * @return array<int, array<string, string>>
     */
    private function collectSlotsForDays(int $duration, array $timeSlotMap, array $days): array
    {
        $slots = $timeSlotMap[$duration] ?? [];

        if (empty($slots)) {
            foreach ($timeSlotMap as $availableSlots) {
                if (!empty($availableSlots)) {
                    $slots = $availableSlots;
                    break;
                }
            }
        }

        if (empty($slots) || empty($days)) {
            return [];
        }

        $filtered = array_filter($slots, function (array $slot) use ($days): bool {
            return in_array((string) ($slot['day'] ?? ''), $days, true);
        });

        return array_values($filtered);
    }

    /**
     * Find a conflict-free slot for a specific faculty/room/block over preferred days.
     *
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @param array<int, string> $preferredDays
     * @param array<int, array<string, mixed>> $existingGenes
     * @return array<string, string>|null
     */
    private function findConflictFreeSlotForGene(
        int $duration,
        array $timeSlotMap,
        array $preferredDays,
        array $existingGenes,
        int $facultyId,
        int $roomId,
        string $block
    ): ?array {
        $candidateSlots = $this->collectSlotsForDays($duration, $timeSlotMap, $preferredDays);

        if (empty($candidateSlots)) {
            return null;
        }

        shuffle($candidateSlots);

        foreach ($candidateSlots as $slot) {
            $probeGene = [
                'faculty_id' => $facultyId,
                'room_id' => $roomId,
                'day' => (string) ($slot['day'] ?? ''),
                'start_time' => (string) ($slot['start'] ?? ''),
                'end_time' => (string) ($slot['end'] ?? ''),
                'block' => $block,
                'is_nstp' => false,
            ];

            if (!$this->hasImmediateHardConflict($probeGene, $existingGenes)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function weekdayDays(): array
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    }

    /**
     * Generate a safe time slot that doesn't overlap with break times.
     * @return array<string, string>
     */
    private function generateSafeTimeSlot(int $durationMinutes): array
    {
        // Try to find a safe slot by iterating through the day
        $cursor = strtotime(self::DEFAULT_DAY_START);
        $end = strtotime(self::DEFAULT_DAY_END);

        while (($cursor + ($durationMinutes * 60)) <= $end) {
            $slotEnd = $cursor + ($durationMinutes * 60);
            $startStr = date('H:i', $cursor);
            $endStr = date('H:i', $slotEnd);
            
            if (!$this->slotOverlapsWithBreakTime($startStr, $endStr)) {
                return [
                    'day' => self::WORKING_DAYS[0],
                    'start' => $startStr,
                    'end' => $endStr,
                ];
            }

            $cursor += self::TIME_SLOT_STEP_MINUTES * 60;
        }

        // If no safe slot found (shouldn't happen with proper break times), return earliest non-break slot
        return [
            'day' => self::WORKING_DAYS[0],
            'start' => '07:00',
            'end' => date('H:i', strtotime('07:00') + ($durationMinutes * 60)),
        ];
    }

    /**
     * @param array<string, mixed> $gene
     * @param array<int, array<string, mixed>> $existingGenes
     */
    private function hasImmediateHardConflict(array $gene, array $existingGenes): bool
    {
        // Check NSTP day constraint
        $isNstp = (bool) ($gene['is_nstp'] ?? false);
        if ($isNstp && (string) $gene['day'] !== self::NSTP_REQUIRED_DAY) {
            return true;
        }

        // Check break time overlap
        if ($this->slotOverlapsWithBreakTime((string) $gene['start_time'], (string) $gene['end_time'])) {
            return true;
        }

        foreach ($existingGenes as $existing) {
            if ((string) $existing['day'] !== (string) $gene['day']) {
                continue;
            }

            if (!$this->timesOverlap(
                (string) $existing['start_time'],
                (string) $existing['end_time'],
                (string) $gene['start_time'],
                (string) $gene['end_time']
            )) {
                continue;
            }

            if ((int) $existing['faculty_id'] === (int) $gene['faculty_id']) {
                return true;
            }

            if ((int) $existing['room_id'] === (int) $gene['room_id']) {
                return true;
            }

            if ((string) $existing['block'] === (string) $gene['block']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $population
     * @return array<string, mixed>
     */
    private function selectOneParent(array $population): array
    {
        $tournamentSize = min(5, count($population));
        $candidates = [];

        for ($i = 0; $i < $tournamentSize; $i++) {
            $candidates[] = $population[array_rand($population)];
        }

        usort($candidates, fn (array $a, array $b): int => ($b['fitness'] <=> $a['fitness']));

        return $candidates[0];
    }

    /**
     * @param array<int, array<string, mixed>> $genes
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    private function repairChromosome(array $genes, array $context): array
    {
        foreach ($genes as $index => $gene) {
            $subjectId = (int) $gene['subject_id'];
            $classType = (string) $gene['class_type'];
            $isNstp = (bool) ($gene['is_nstp'] ?? false);
            $duration = (int) $gene['duration_minutes'];

            // Fix faculty assignment if invalid
            $facultyCandidates = $context['faculty_map'][$subjectId] ?? [];
            if (!in_array((int) $gene['faculty_id'], $facultyCandidates, true) && !empty($facultyCandidates)) {
                $genes[$index]['faculty_id'] = (int) $facultyCandidates[array_rand($facultyCandidates)];
            }

            // Fix room assignment if invalid
            $roomPool = $context['rooms_by_type'][$classType] ?? collect();
            if ($roomPool instanceof Collection && $roomPool->isNotEmpty()) {
                $validRoomIds = $roomPool->pluck('id')->map(fn ($id): int => (int) $id)->all();
                if (!in_array((int) $gene['room_id'], $validRoomIds, true)) {
                    $genes[$index]['room_id'] = (int) $roomPool->random()->id;
                }
            }

            // Fix NSTP constraint: must be on Saturday
            if ($isNstp && (string) $gene['day'] !== self::NSTP_REQUIRED_DAY) {
                $slot = $this->pickRandomTimeSlotForDay(
                    $duration,
                    $context['time_slots'],
                    self::NSTP_REQUIRED_DAY
                );
                $genes[$index]['day'] = $slot['day'];
                $genes[$index]['start_time'] = $slot['start'];
                $genes[$index]['end_time'] = $slot['end'];
            }

            // Fix NSTP constraint: must be exactly 3 hours
            if ($isNstp && $duration !== self::NSTP_REQUIRED_DURATION_MINUTES) {
                $genes[$index]['duration_minutes'] = self::NSTP_REQUIRED_DURATION_MINUTES;
                // Update times to reflect 3-hour duration
                $startTime = strtotime((string) $gene['start_time']);
                $endTime = $startTime + (self::NSTP_REQUIRED_DURATION_MINUTES * 60);
                $genes[$index]['end_time'] = date('H:i', $endTime);
            }

            // Fix break time overlap
            if ($this->slotOverlapsWithBreakTime((string) $gene['start_time'], (string) $gene['end_time'])) {
                $isNstpGene = (bool) ($gene['is_nstp'] ?? false);
                $slot = $isNstpGene
                    ? $this->pickRandomTimeSlotForDay(
                        $duration,
                        $context['time_slots'],
                        self::NSTP_REQUIRED_DAY
                    )
                    : $this->pickRandomTimeSlot($duration, $context['time_slots']);
                
                $genes[$index]['day'] = $slot['day'];
                $genes[$index]['start_time'] = $slot['start'];
                $genes[$index]['end_time'] = $slot['end'];
            }

            if (!$isNstp && (string) ($genes[$index]['day'] ?? '') === self::NSTP_REQUIRED_DAY) {
                $otherGenes = array_values(array_filter($genes, fn ($_, $key): bool => $key !== $index, ARRAY_FILTER_USE_BOTH));

                $weekdaySlot = $this->findConflictFreeSlotForGene(
                    $duration,
                    $context['time_slots'],
                    $this->weekdayDays(),
                    $otherGenes,
                    (int) ($genes[$index]['faculty_id'] ?? 0),
                    (int) ($genes[$index]['room_id'] ?? 0),
                    (string) ($genes[$index]['block'] ?? '')
                );

                if ($weekdaySlot !== null) {
                    $genes[$index]['day'] = $weekdaySlot['day'];
                    $genes[$index]['start_time'] = $weekdaySlot['start'];
                    $genes[$index]['end_time'] = $weekdaySlot['end'];
                    $genes[$index]['overflow_saturday'] = false;
                } else {
                    $genes[$index]['overflow_saturday'] = true;
                }
            } elseif (!$isNstp) {
                $genes[$index]['overflow_saturday'] = false;
            }
        }

        return $genes;
    }

    /**
     * @param array<int, array<string, mixed>> $genes
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function analyzeGenes(array $genes, array $context): array
    {
        $facultyConflicts = 0;
        $roomConflicts = 0;
        $blockConflicts = 0;
        $invalidFacultyAssignment = 0;
        $invalidRoomType = 0;
        $nstpViolations = 0;
        $breakTimeConflicts = 0;
        $nonNstpSaturdayViolations = 0;
        $overflowSaturdayAssignments = 0;

        $facultyHours = [];
        $facultyDaySlots = [];
        $blockDaySlots = [];

        foreach ($genes as $gene) {
            $subjectId = (int) $gene['subject_id'];
            $facultyId = (int) $gene['faculty_id'];
            $durationHours = ((int) $gene['duration_minutes']) / 60;
            $day = (string) $gene['day'];
            $isNstp = (bool) ($gene['is_nstp'] ?? false);

            $allowedFaculty = $context['faculty_map'][$subjectId] ?? [];
            if (!in_array($facultyId, $allowedFaculty, true)) {
                $invalidFacultyAssignment++;
            }

            $expectedType = (string) $gene['class_type'];
            $roomType = $this->roomTypeForId((int) $gene['room_id'], $context['rooms_by_type']);
            if ($roomType !== $expectedType) {
                $invalidRoomType++;
            }

            // Check NSTP constraints
            if ($isNstp) {
                if ($day !== self::NSTP_REQUIRED_DAY) {
                    $nstpViolations++;
                }
                if ((int) $gene['duration_minutes'] !== self::NSTP_REQUIRED_DURATION_MINUTES) {
                    $nstpViolations++;
                }
            } elseif ($day === self::NSTP_REQUIRED_DAY) {
                if ((bool) ($gene['overflow_saturday'] ?? false)) {
                    $overflowSaturdayAssignments++;
                } else {
                    $nonNstpSaturdayViolations++;
                }
            }

            // Check break time conflicts
            if ($this->slotOverlapsWithBreakTime((string) $gene['start_time'], (string) $gene['end_time'])) {
                $breakTimeConflicts++;
            }

            if (!isset($facultyHours[$facultyId])) {
                $facultyHours[$facultyId] = 0.0;
            }
            $facultyHours[$facultyId] += $durationHours;

            $facultyDaySlots[$facultyId][$day][] = $gene;
            $blockDaySlots[(string) $gene['block']][$day][] = $gene;
        }

        // Pairwise conflict checks.
        $count = count($genes);
        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $left = $genes[$i];
                $right = $genes[$j];

                if ((string) $left['day'] !== (string) $right['day']) {
                    continue;
                }

                if (!$this->timesOverlap(
                    (string) $left['start_time'],
                    (string) $left['end_time'],
                    (string) $right['start_time'],
                    (string) $right['end_time']
                )) {
                    continue;
                }

                if ((int) $left['faculty_id'] === (int) $right['faculty_id']) {
                    $facultyConflicts++;
                }

                if ((int) $left['room_id'] === (int) $right['room_id']) {
                    $roomConflicts++;
                }

                if ((string) $left['block'] === (string) $right['block']) {
                    $blockConflicts++;
                }
            }
        }

        $overloadHours = 0.0;
        $workloadImbalance = 0;

        if (!empty($facultyHours)) {
            $loads = array_values($facultyHours);
            $mean = array_sum($loads) / max(1, count($loads));
            $variance = 0.0;
            foreach ($loads as $hours) {
                $variance += pow($hours - $mean, 2);
            }
            $variance /= max(1, count($loads));
            $stdDev = sqrt($variance);
            $workloadImbalance = (int) round($stdDev);

            foreach ($facultyHours as $facultyId => $hours) {
                $maxLoad = $this->getFacultyMaxLoad((int) $facultyId);
                if ($maxLoad !== null && $hours > $maxLoad) {
                    $overloadHours += ($hours - $maxLoad);
                }
            }
        }

        $gapPenalty = $this->calculateGapPenalty($facultyDaySlots, $blockDaySlots);
        $roomSwitchPenalty = $this->calculateRoomSwitchPenalty($facultyDaySlots);

        $hardConflicts = $facultyConflicts
            + $roomConflicts
            + $blockConflicts
            + $invalidFacultyAssignment
            + $invalidRoomType
            + $nstpViolations
            + $breakTimeConflicts;

        $softPenalties = $workloadImbalance + $gapPenalty + $roomSwitchPenalty;

        return [
            'hard_conflicts' => $hardConflicts,
            'faculty_conflicts' => $facultyConflicts,
            'room_conflicts' => $roomConflicts,
            'block_conflicts' => $blockConflicts,
            'invalid_faculty_assignment' => $invalidFacultyAssignment,
            'invalid_room_type' => $invalidRoomType,
            'nstp_violations' => $nstpViolations,
            'break_time_conflicts' => $breakTimeConflicts,
            'non_nstp_saturday_violations' => $nonNstpSaturdayViolations,
            'overflow_saturday_assignments' => $overflowSaturdayAssignments,
            'overload_hours' => round($overloadHours, 2),
            'soft_penalties' => $softPenalties,
            'workload_imbalance' => $workloadImbalance,
            'schedule_gap_penalty' => $gapPenalty,
            'room_switch_penalty' => $roomSwitchPenalty,
        ];
    }

    /**
     * @param array{lecture:Collection<int,Room>,lab:Collection<int,Room>} $roomsByType
     */
    private function roomTypeForId(int $roomId, array $roomsByType): string
    {
        if ($roomsByType['lab']->contains(fn (Room $room): bool => (int) $room->id === $roomId)) {
            return 'lab';
        }

        if ($roomsByType['lecture']->contains(fn (Room $room): bool => (int) $room->id === $roomId)) {
            return 'lecture';
        }

        return 'unknown';
    }

    private function getFacultyMaxLoad(int $facultyId): ?float
    {
        $instructor = User::query()->find($facultyId);
        if (!$instructor) {
            return null;
        }

        $limits = $instructor->getWorkloadLimits();
        $maxLecture = $limits['max_lecture_hours'] ?? null;
        $maxLab = $limits['max_lab_hours'] ?? null;

        if ($maxLecture === null && $maxLab === null) {
            return null;
        }

        return (float) (($maxLecture ?? 0) + ($maxLab ?? 0));
    }

    /**
     * @param array<int, array<string, array<int, array<string, mixed>>>> $facultyDaySlots
     * @param array<string, array<string, array<int, array<string, mixed>>>> $blockDaySlots
     */
    private function calculateGapPenalty(array $facultyDaySlots, array $blockDaySlots): int
    {
        $penalty = 0;

        foreach ($facultyDaySlots as $days) {
            foreach ($days as $daySlots) {
                $penalty += $this->countDailyGapUnits($daySlots);
            }
        }

        foreach ($blockDaySlots as $days) {
            foreach ($days as $daySlots) {
                $penalty += $this->countDailyGapUnits($daySlots);
            }
        }

        return $penalty;
    }

    /**
     * @param array<int, array<string, mixed>> $daySlots
     */
    private function countDailyGapUnits(array $daySlots): int
    {
        if (count($daySlots) <= 1) {
            return 0;
        }

        usort($daySlots, fn (array $a, array $b): int => strcmp((string) $a['start_time'], (string) $b['start_time']));

        $penalty = 0;
        for ($i = 1; $i < count($daySlots); $i++) {
            $prevEnd = strtotime((string) $daySlots[$i - 1]['end_time']);
            $currentStart = strtotime((string) $daySlots[$i]['start_time']);
            $gapMinutes = (int) (($currentStart - $prevEnd) / 60);

            if ($gapMinutes > 30) {
                $penalty += (int) floor($gapMinutes / 30);
            }
        }

        return $penalty;
    }

    /**
     * @param array<int, array<string, array<int, array<string, mixed>>>> $facultyDaySlots
     */
    private function calculateRoomSwitchPenalty(array $facultyDaySlots): int
    {
        $penalty = 0;

        foreach ($facultyDaySlots as $days) {
            foreach ($days as $daySlots) {
                usort($daySlots, fn (array $a, array $b): int => strcmp((string) $a['start_time'], (string) $b['start_time']));

                for ($i = 1; $i < count($daySlots); $i++) {
                    $prev = $daySlots[$i - 1];
                    $current = $daySlots[$i];

                    $prevEnd = strtotime((string) $prev['end_time']);
                    $currentStart = strtotime((string) $current['start_time']);
                    $gapMinutes = (int) (($currentStart - $prevEnd) / 60);

                    if ($gapMinutes <= 30 && (int) $prev['room_id'] !== (int) $current['room_id']) {
                        $penalty++;
                    }
                }
            }
        }

        return $penalty;
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
     * Check if a time slot overlaps with any defined break time.
     */
    private function slotOverlapsWithBreakTime(string $slotStart, string $slotEnd): bool
    {
        foreach (self::BREAK_TIMES as $breakTime) {
            if ($this->timesOverlap($slotStart, $slotEnd, $breakTime['start'], $breakTime['end'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pick a random time slot for a specific day.
     *
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @return array<string, string>
     */
    private function pickRandomTimeSlotForDay(int $duration, array $timeSlotMap, string $targetDay): array
    {
        $slots = $timeSlotMap[$duration] ?? [];
        
        // Filter slots for the target day
        $daySlots = array_filter($slots, fn (array $slot): bool => $slot['day'] === $targetDay);
        $daySlots = array_values($daySlots);

        if (!empty($daySlots)) {
            return $daySlots[array_rand($daySlots)];
        }

        // Fallback: try to find any slots from any duration for this day
        foreach ($timeSlotMap as $availableSlots) {
            $daySlots = array_filter($availableSlots, fn (array $slot): bool => $slot['day'] === $targetDay);
            if (!empty($daySlots)) {
                $daySlots = array_values($daySlots);
                return $daySlots[array_rand($daySlots)];
            }
        }

        // Last resort: generate a safe slot for the target day
        return $this->generateSafeTimeSlotForDay($duration, $targetDay);
    }

    /**
     * Generate a safe time slot for a specific day that doesn't overlap with break times.
     * @return array<string, string>
     */
    private function generateSafeTimeSlotForDay(int $durationMinutes, string $targetDay): array
    {
        $cursor = strtotime(self::DEFAULT_DAY_START);
        $end = strtotime(self::DEFAULT_DAY_END);

        while (($cursor + ($durationMinutes * 60)) <= $end) {
            $slotEnd = $cursor + ($durationMinutes * 60);
            $startStr = date('H:i', $cursor);
            $endStr = date('H:i', $slotEnd);
            
            if (!$this->slotOverlapsWithBreakTime($startStr, $endStr)) {
                return [
                    'day' => $targetDay,
                    'start' => $startStr,
                    'end' => $endStr,
                ];
            }

            $cursor += self::TIME_SLOT_STEP_MINUTES * 60;
        }

        // If no safe slot found, return earliest slot anyway (shouldn't happen)
        return [
            'day' => $targetDay,
            'start' => '07:00',
            'end' => date('H:i', strtotime('07:00') + ($durationMinutes * 60)),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $genes
     * @return array<int, array<string, mixed>>
     */
    private function buildFacultyWorkloadReport(array $genes): array
    {
        $hoursByFaculty = [];

        foreach ($genes as $gene) {
            $facultyId = (int) $gene['faculty_id'];
            $hours = ((int) $gene['duration_minutes']) / 60;
            $hoursByFaculty[$facultyId] = ($hoursByFaculty[$facultyId] ?? 0) + $hours;
        }

        $report = [];
        foreach ($hoursByFaculty as $facultyId => $hours) {
            $user = User::query()->find($facultyId);
            if (!$user) {
                continue;
            }

            $maxLoad = $this->getFacultyMaxLoad($facultyId);
            $overload = $maxLoad === null ? 0 : max(0, $hours - $maxLoad);

            $report[] = [
                'faculty_id' => $facultyId,
                'faculty_name' => $user->full_name,
                'total_load' => round($hours, 2),
                'max_load' => $maxLoad,
                'overload_hours' => round($overload, 2),
                'status' => $overload > 0 ? 'Overloaded' : 'Normal',
            ];
        }

        return $report;
    }
}
