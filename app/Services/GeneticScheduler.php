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

class GeneticScheduler
{
    private const BASE_FITNESS = 10000;
    private const HARD_CONFLICT_WEIGHT = 1000;
    private const OVERLOAD_HOUR_WEIGHT = 200;
    private const SOFT_PENALTY_WEIGHT = 50;

    private const WORKING_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    private const DEFAULT_DAY_START = '07:00';
    private const DEFAULT_DAY_END = '19:00';
    private const TIME_SLOT_STEP_MINUTES = 30;

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

        $program = Program::query()->findOrFail($programId);
        $academicYear = AcademicYear::query()->findOrFail($academicYearId);

        $subjects = $this->loadSubjects($programId, $yearLevel, $semester);
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

        $missingFacultySubjects = $this->findSubjectsWithNoFaculty($subjects, $facultyMap);
        if (!empty($missingFacultySubjects)) {
            return [
                'success' => false,
                'message' => 'Some subjects have no faculty assignment in faculty_load for this term/block: ' . implode(', ', $missingFacultySubjects),
            ];
        }

        $roomsByType = $this->loadRoomsByType();
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
                    'fitness_formula' => '10000 - (hard_conflicts*1000) - (overload_hours*200) - (soft_penalties*50)',
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

            return [
                'success' => true,
                'schedule_id' => $schedule->id,
                'program' => $program->program_name,
                'block' => (string) $context['block_section'],
                'fitness_score' => (float) $bestChromosome['fitness'],
                'metrics' => $bestChromosome['metrics'] ?? [],
                'genes' => $bestChromosome['genes'],
                'faculty_workloads' => $this->buildFacultyWorkloadReport($bestChromosome['genes']),
            ];
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
        $overloadHours = (float) $analysis['overload_hours'];
        $softPenalties = (int) $analysis['soft_penalties'];

        $fitness = self::BASE_FITNESS
            - ($hardConflicts * self::HARD_CONFLICT_WEIGHT)
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

            $mutationType = random_int(1, 3);

            if ($mutationType === 1) {
                $slot = $this->pickRandomTimeSlot((int) $gene['duration_minutes'], $context['time_slots']);
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

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    private function loadSubjects(int $programId, int $yearLevel, string $semester): Collection
    {
        return Subject::query()
            ->where('is_active', true)
            ->whereHas('programs', function ($query) use ($programId, $yearLevel, $semester): void {
                $query->where('program_id', $programId)
                    ->where('year_level', $yearLevel)
                    ->where('semester', $semester);
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
            $lectureMinutes = (int) round((float) $subject->lecture_hours * 60);
            $labMinutes = (int) round((float) $subject->lab_hours * 60);

            foreach ($this->splitIntoDurations($lectureMinutes, 60) as $duration) {
                $sessions[] = [
                    'subject_id' => (int) $subject->id,
                    'class_type' => 'lecture',
                    'duration_minutes' => $duration,
                    'block' => $blockSection,
                ];
            }

            foreach ($this->splitIntoDurations($labMinutes, 180) as $duration) {
                $sessions[] = [
                    'subject_id' => (int) $subject->id,
                    'class_type' => 'lab',
                    'duration_minutes' => $duration,
                    'block' => $blockSection,
                ];
            }
        }

        return $sessions;
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
            $slots[] = [
                'day' => $day,
                'start' => date('H:i', $cursor),
                'end' => date('H:i', $slotEnd),
            ];

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

        $facultyCandidates = $context['faculty_map'][$subjectId] ?? [];
        $roomPool = $context['rooms_by_type'][$classType] ?? collect();

        $attempts = 60;
        while ($attempts-- > 0) {
            $facultyId = (int) $facultyCandidates[array_rand($facultyCandidates)];
            /** @var Room $room */
            $room = $roomPool->random();
            $slot = $this->pickRandomTimeSlot($duration, $context['time_slots']);

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
            ];

            if (!$this->hasImmediateHardConflict($gene, $existingGenes)) {
                return $gene;
            }
        }

        // If no conflict-free placement is found quickly, still return a gene
        // so GA can repair/penalize it instead of stalling.
        $fallbackSlot = $this->pickRandomTimeSlot($duration, $context['time_slots']);

        return [
            'subject_id' => $subjectId,
            'faculty_id' => (int) $facultyCandidates[array_rand($facultyCandidates)],
            'room_id' => (int) $roomPool->random()->id,
            'day' => $fallbackSlot['day'],
            'start_time' => $fallbackSlot['start'],
            'end_time' => $fallbackSlot['end'],
            'block' => (string) $session['block'],
            'class_type' => $classType,
            'duration_minutes' => $duration,
        ];
    }

    /**
     * @param array<int, array<int, array<string, string>>> $timeSlotMap
     * @return array<string, string>
     */
    private function pickRandomTimeSlot(int $duration, array $timeSlotMap): array
    {
        $slots = $timeSlotMap[$duration] ?? [];

        if (empty($slots)) {
            $slots = $timeSlotMap[array_key_first($timeSlotMap)] ?? [];
        }

        if (empty($slots)) {
            return [
                'day' => self::WORKING_DAYS[0],
                'start' => self::DEFAULT_DAY_START,
                'end' => date('H:i', strtotime(self::DEFAULT_DAY_START) + ($duration * 60)),
            ];
        }

        /** @var array<string, string> $slot */
        $slot = $slots[array_rand($slots)];

        return $slot;
    }

    /**
     * @param array<string, mixed> $gene
     * @param array<int, array<string, mixed>> $existingGenes
     */
    private function hasImmediateHardConflict(array $gene, array $existingGenes): bool
    {
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

            $facultyCandidates = $context['faculty_map'][$subjectId] ?? [];
            if (!in_array((int) $gene['faculty_id'], $facultyCandidates, true) && !empty($facultyCandidates)) {
                $genes[$index]['faculty_id'] = (int) $facultyCandidates[array_rand($facultyCandidates)];
            }

            $roomPool = $context['rooms_by_type'][$classType] ?? collect();
            if ($roomPool instanceof Collection && $roomPool->isNotEmpty()) {
                $validRoomIds = $roomPool->pluck('id')->map(fn ($id): int => (int) $id)->all();
                if (!in_array((int) $gene['room_id'], $validRoomIds, true)) {
                    $genes[$index]['room_id'] = (int) $roomPool->random()->id;
                }
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

        $facultyHours = [];
        $facultyDaySlots = [];
        $blockDaySlots = [];

        foreach ($genes as $gene) {
            $subjectId = (int) $gene['subject_id'];
            $facultyId = (int) $gene['faculty_id'];
            $durationHours = ((int) $gene['duration_minutes']) / 60;
            $day = (string) $gene['day'];

            $allowedFaculty = $context['faculty_map'][$subjectId] ?? [];
            if (!in_array($facultyId, $allowedFaculty, true)) {
                $invalidFacultyAssignment++;
            }

            $expectedType = (string) $gene['class_type'];
            $roomType = $this->roomTypeForId((int) $gene['room_id'], $context['rooms_by_type']);
            if ($roomType !== $expectedType) {
                $invalidRoomType++;
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
            + $invalidRoomType;

        $softPenalties = $workloadImbalance + $gapPenalty + $roomSwitchPenalty;

        return [
            'hard_conflicts' => $hardConflicts,
            'faculty_conflicts' => $facultyConflicts,
            'room_conflicts' => $roomConflicts,
            'block_conflicts' => $blockConflicts,
            'invalid_faculty_assignment' => $invalidFacultyAssignment,
            'invalid_room_type' => $invalidRoomType,
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

        $limits = $instructor->getContractLoadLimits();
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
