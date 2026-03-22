<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subject;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * GeneticAlgorithmEngine
 *
 * Core genetic algorithm implementation for automated schedule generation.
 * Handles population management, evolution, and constraint-aware optimization.
 */
class GeneticAlgorithmEngine
{
    protected ConstraintValidator $validator;
    protected int $populationSize;
    protected int $generations;
    protected float $mutationRate;
    protected float $crossoverRate;
    protected int $eliteSize;
    protected int $missingHoursPenaltyPerHour = 500;

    // Instructor cache — populated in evolve(), used in calculateFitness/mutate to avoid DB queries
    protected Collection $cachedInstructors;
    protected Collection $cachedSubjects;

    // Working days
    protected array $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Global time window
    protected string $globalStartTime = '07:00';
    protected string $globalEndTime   = '19:00';

    protected function isLaboratoryRoom(Room $room): bool
    {
        $roomType = strtolower(trim((string) ($room->room_type ?? '')));
        $roomName = strtolower(trim((string) ($room->room_name ?? '')));
        $roomCode = strtolower(trim((string) ($room->room_code ?? '')));

        return $roomType === 'laboratory'
            || str_contains($roomType, 'lab')
            || str_contains($roomName, 'lab')
            || str_contains($roomCode, 'lab');
    }

    protected function isNstpSubject(Subject $subject): bool
    {
        $subjectCode = strtolower(trim((string) ($subject->subject_code ?? '')));
        $subjectName = strtolower(trim((string) ($subject->subject_name ?? '')));

        return str_contains($subjectCode, 'nstp') || str_contains($subjectName, 'nstp');
    }

    protected function isNstpGene(array $gene): bool
    {
        $subjectCode = strtolower(trim((string) ($gene['subject_code'] ?? '')));

        return str_contains($subjectCode, 'nstp');
    }

    protected function getAllowedDaysForSubject(Subject $subject): array
    {
        if ($this->isNstpSubject($subject)) {
            return ['Saturday'];
        }

        return array_values(array_filter(
            $this->workingDays,
            fn ($day) => $day !== 'Saturday'
        ));
    }

    protected function getEligibleRooms(Collection $rooms, string $type): Collection
    {
        if ($type === 'lab') {
            return $rooms->filter(fn ($room) => $this->isLaboratoryRoom($room))->values();
        }

        return $rooms->reject(fn ($room) => $this->isLaboratoryRoom($room))->values();
    }

    public function __construct(
        int $populationSize = 50,
        int $generations    = 100,
        float $mutationRate  = 0.15,
        float $crossoverRate = 0.80,
        int $eliteSize      = 5
    ) {
        $this->validator          = new ConstraintValidator();
        $this->populationSize     = $populationSize;
        $this->generations        = $generations;
        $this->mutationRate       = $mutationRate;
        $this->crossoverRate      = $crossoverRate;
        $this->eliteSize          = $eliteSize;
        $this->cachedInstructors  = collect();
        $this->cachedSubjects     = collect();
    }

    /**
     * Generate time slots dynamically based on duration
     */
    public function generateTimeSlots(
        int $durationMinutes          = 60,
        ?string $instructorSchemeStart = null,
        ?string $instructorSchemeEnd   = null
    ): array {
        $start = $instructorSchemeStart ?? $this->globalStartTime;
        $end   = $instructorSchemeEnd   ?? $this->globalEndTime;

        // If scheme window is too narrow for the duration, fall back to global window
        $startTime = Carbon::parse($start);
        $endTime   = Carbon::parse($end);

        if ($endTime->diffInMinutes($startTime) < $durationMinutes) {
            $startTime = Carbon::parse($this->globalStartTime);
            $endTime   = Carbon::parse($this->globalEndTime);
        }

        $slots  = [];
        $period = CarbonPeriod::create($startTime, "{$durationMinutes} minutes", $endTime);

        foreach ($period as $time) {
            $slotEnd = $time->copy()->addMinutes($durationMinutes);
            if ($slotEnd->lessThanOrEqualTo($endTime)) {
                $slots[] = [
                    'start' => $time->format('H:i'),
                    'end'   => $slotEnd->format('H:i'),
                ];
            }
        }

        return $slots;
    }

    protected function getCandidateTimeSlots(
        Subject $subject,
        int $durationMinutes,
        ?string $instructorSchemeStart = null,
        ?string $instructorSchemeEnd = null
    ): array {
        if ($this->isNstpSubject($subject)) {
            return $durationMinutes === 180
                ? [['start' => '07:00', 'end' => '10:00']]
                : [];
        }

        $slots = $this->generateTimeSlots(
            $durationMinutes,
            $instructorSchemeStart,
            $instructorSchemeEnd
        );

        if (empty($slots)) {
            $slots = $this->generateTimeSlots($durationMinutes);
        }

        return $slots;
    }

    protected function attemptDeterministicPlacement(
        Subject $subject,
        string $type,
        float $duration,
        Collection $instructors,
        Collection $rooms,
        string $section,
        array &$existingGenes,
        array &$facultyLoads,
        array $allowedDays
    ): ?array {
        $durationMinutes = (int) round($duration * 60);

        foreach ($instructors->shuffle() as $instructor) {
            $timeSlots = $this->getCandidateTimeSlots(
                $subject,
                $durationMinutes,
                $instructor->daily_scheme_start ?? null,
                $instructor->daily_scheme_end ?? null
            );

            if (empty($timeSlots)) {
                continue;
            }

            foreach ($allowedDays as $day) {
                foreach ($timeSlots as $timeSlot) {
                    foreach ($rooms->shuffle() as $room) {
                        $existingCollection = collect($existingGenes);

                        if (!$this->validator->checkRoomAvailability($room->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                            continue;
                        }

                        if ($this->validator->checkInstructorTimeConflict($instructor->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                            continue;
                        }

                        if ($this->validator->checkSectionTimeConflict($section, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                            continue;
                        }

                        if (!isset($facultyLoads[$instructor->id])) {
                            $facultyLoads[$instructor->id] = ['lecture' => 0, 'lab' => 0];
                        }

                        $facultyLoads[$instructor->id][$type] += $duration;

                        $loadValidation = $this->validator->validateFacultyLoad(
                            $instructor,
                            $facultyLoads[$instructor->id]['lecture'],
                            $facultyLoads[$instructor->id]['lab']
                        );

                        if (!$loadValidation['valid']) {
                            $facultyLoads[$instructor->id][$type] -= $duration;
                            continue;
                        }

                        $gene = [
                            'subject_id' => $subject->id,
                            'subject_code' => $subject->subject_code,
                            'instructor_id' => $instructor->id,
                            'room_id' => $room->id,
                            'day' => $day,
                            'start_time' => $timeSlot['start'],
                            'end_time' => $timeSlot['end'],
                            'section' => $section,
                            'type' => $type,
                            'duration' => $duration,
                        ];

                        $existingGenes[] = $gene;

                        return $gene;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Create initial population of chromosomes
     */
    public function createInitialPopulation(
        Collection $subjects,
        Collection $instructors,
        Collection $rooms,
        string $section,
        int $yearLevel,
        string $semester
    ): array {
        $population = [];

        for ($i = 0; $i < $this->populationSize; $i++) {
            $population[] = $this->createChromosome($subjects, $instructors, $rooms, $section);
        }

        return $population;
    }

    /**
     * Create a single chromosome (complete schedule)
     */
    protected function createChromosome(
        Collection $subjects,
        Collection $instructors,
        Collection $rooms,
        string $section
    ): array {
        $genes        = [];
        $facultyLoads = [];

        foreach ($subjects as $subject) {
            if ($this->isNstpSubject($subject)) {
                $lectureRooms = $this->getEligibleRooms($rooms, 'lecture');
                if ($lectureRooms->isEmpty()) {
                    Log::warning('GeneticAlgorithmEngine: no lecture rooms available for NSTP subject', [
                        'subject_id' => $subject->id,
                        'subject_code' => $subject->subject_code,
                    ]);
                    continue;
                }

                $nstpGenes = $this->createGenesForSubject(
                    $subject,
                    'lecture',
                    3.0,
                    $instructors,
                    $lectureRooms,
                    $section,
                    $genes,
                    $facultyLoads
                );
                $genes = array_merge($genes, $nstpGenes);
                continue;
            }

            if ($subject->lecture_hours > 0) {
                $lectureRooms = $this->getEligibleRooms($rooms, 'lecture');
                if ($lectureRooms->isEmpty()) {
                    Log::warning('GeneticAlgorithmEngine: no lecture rooms available for lecture subject', [
                        'subject_id' => $subject->id,
                        'subject_code' => $subject->subject_code,
                    ]);
                    continue;
                }

                $lectureGenes = $this->createGenesForSubject(
                    $subject, 'lecture', $subject->lecture_hours,
                    $instructors, $lectureRooms, $section, $genes, $facultyLoads
                );
                $genes = array_merge($genes, $lectureGenes);
            }

            if ($subject->lab_hours > 0) {
                $labRooms = $this->getEligibleRooms($rooms, 'lab');
                if ($labRooms->isEmpty()) {
                    Log::warning('GeneticAlgorithmEngine: no laboratory rooms available for lab subject', [
                        'subject_id'   => $subject->id,
                        'subject_code' => $subject->subject_code,
                    ]);
                    continue;
                }

                $labGenes = $this->createGenesForSubject(
                    $subject, 'lab', $subject->lab_hours,
                    $instructors, $labRooms, $section, $genes, $facultyLoads
                );
                $genes = array_merge($genes, $labGenes);
            }
        }

        return [
            'genes'        => $genes,
            'fitness'      => 0,
            'faculty_loads'=> $facultyLoads,
        ];
    }

    /**
     * Create genes for a specific subject (may span multiple time slots)
     */
    protected function createGenesForSubject(
        Subject $subject,
        string $type,
        float $hours,
        Collection $instructors,
        Collection $rooms,
        string $section,
        array &$existingGenes,
        array &$facultyLoads
    ): array {
        // Guard: nothing to schedule against
        if ($instructors->isEmpty()) {
            Log::warning('createGenesForSubject: instructor pool is empty', [
                'subject_code' => $subject->subject_code, 'type' => $type,
            ]);
            return [];
        }

        if ($rooms->isEmpty()) {
            Log::warning('createGenesForSubject: room pool is empty', [
                'subject_code' => $subject->subject_code, 'type' => $type,
            ]);
            return [];
        }

        $genes = [];
        $remainingHours = $hours;
        $maxAttempts = max(100, (int) ceil($hours * 40));
        $allowedDays = $this->getAllowedDaysForSubject($subject);

        if (empty($allowedDays)) {
            Log::warning('createGenesForSubject: subject has no allowed days', [
                'subject_code' => $subject->subject_code,
                'type' => $type,
            ]);
            return [];
        }

        while ($remainingHours > 0 && $maxAttempts > 0) {
            $maxAttempts--;

            $duration = $this->isNstpSubject($subject)
                ? min($remainingHours, 3.0)
                : min($remainingHours, $this->selectDuration($type, $remainingHours));
            $durationMinutes = (int) round($duration * 60);

            // Pick a random instructor
            $instructor = $instructors->random();

            $timeSlots = $this->getCandidateTimeSlots(
                $subject,
                $durationMinutes,
                $instructor->daily_scheme_start ?? null,
                $instructor->daily_scheme_end   ?? null
            );

            if (empty($timeSlots)) {
                continue;
            }

            $timeSlot = $timeSlots[array_rand($timeSlots)];
            $day      = $allowedDays[array_rand($allowedDays)];
            $room     = $rooms->random();

            $gene = [
                'subject_id'    => $subject->id,
                'subject_code'  => $subject->subject_code,
                'instructor_id' => $instructor->id,
                'room_id'       => $room->id,
                'day'           => $day,
                'start_time'    => $timeSlot['start'],
                'end_time'      => $timeSlot['end'],
                'section'       => $section,
                'type'          => $type,
                'duration'      => $duration,
            ];

            $existingCollection = collect($existingGenes);

            // Hard conflict checks
            if (!$this->validator->checkRoomAvailability($room->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue;
            }
            if ($this->validator->checkInstructorTimeConflict($instructor->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue;
            }
            if ($this->validator->checkSectionTimeConflict($section, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue;
            }

            // Faculty load check
            if (!isset($facultyLoads[$instructor->id])) {
                $facultyLoads[$instructor->id] = ['lecture' => 0, 'lab' => 0];
            }

            $facultyLoads[$instructor->id][$type] += $duration;

            $loadValidation = $this->validator->validateFacultyLoad(
                $instructor,
                $facultyLoads[$instructor->id]['lecture'],
                $facultyLoads[$instructor->id]['lab']
            );

            if (!$loadValidation['valid']) {
                $facultyLoads[$instructor->id][$type] -= $duration;
                continue;
            }

            // Gene is valid
            $genes[]         = $gene;
            $existingGenes[] = $gene;
            $remainingHours -= $duration;

        }

        while ($remainingHours > 0) {
            $duration = $this->isNstpSubject($subject)
                ? min($remainingHours, 3.0)
                : min($remainingHours, $this->selectDuration($type, $remainingHours));
            $fallbackGene = $this->attemptDeterministicPlacement(
                $subject,
                $type,
                $duration,
                $instructors,
                $rooms,
                $section,
                $existingGenes,
                $facultyLoads,
                $allowedDays
            );

            if (!$fallbackGene) {
                break;
            }

            $genes[] = $fallbackGene;
            $remainingHours -= $duration;
        }

        if ($remainingHours > 0) {
            Log::warning('Unable to fully place subject hours during chromosome generation', [
                'subject_id' => $subject->id,
                'subject_code' => $subject->subject_code,
                'type' => $type,
                'requested_hours' => $hours,
                'scheduled_hours' => $hours - $remainingHours,
                'remaining_hours' => $remainingHours,
                'section' => $section,
            ]);
        }

        return $genes;
    }

    /**
     * Select duration for a gene (prefer standard durations)
     */
    protected function selectDuration(string $type, float $remainingHours): float
    {
        if ($type === 'lab') {
            if ($remainingHours >= 3) {
                return 3.0;
            }

            if ($remainingHours >= 2) {
                return 2.0;
            }

            return $remainingHours;
        }

        return 1.0;
    }

    /**
     * Calculate fitness for entire population
     */
    public function evaluatePopulation(array &$population, Collection $instructors): void
    {
        foreach ($population as &$chromosome) {
            $chromosome['fitness'] = $this->calculateFitness($chromosome, $instructors);
        }

        usort($population, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
    }

    /**
     * Calculate fitness score for a chromosome
     */
    protected function calculateFitness(array $chromosome, Collection $instructors): float
    {
        $baseScore    = 1000;
        $penalty      = 0;
        $genes        = $chromosome['genes'];
        $facultyLoads = $chromosome['faculty_loads'];

        foreach ($genes as $index => $gene) {
            $existingGenes = collect(array_slice($genes, 0, $index));
            $penalty += $this->validator->calculateGenePenalty($gene, $existingGenes, $facultyLoads, $instructors);
        }

        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = $this->cachedInstructors[$instructorId] ?? null;
            if ($instructor) {
                $loadValidation = $this->validator->validateFacultyLoad(
                    $instructor,
                    $loads['lecture'],
                    $loads['lab']
                );
                $penalty += $loadValidation['penalty'];
            }
        }

        $geneCollection = collect($genes);
        $instructorIds  = array_unique(array_column($genes, 'instructor_id'));

        foreach ($instructorIds as $instructorId) {
            $instructor = $this->cachedInstructors[$instructorId] ?? null;
            if ($instructor) {
                foreach ($this->workingDays as $day) {
                    $breakCheck = $this->validator->hasBreakConflict($instructor, $geneCollection, $day);
                    $penalty   += $breakCheck['penalty'];
                }
            }
        }

        $penalty += $this->calculateMissingHoursPenalty($genes);

        return max(0, $baseScore - $penalty);
    }

    protected function calculateMissingHoursPenalty(array $genes): int
    {
        $scheduledHours = [];

        foreach ($genes as $gene) {
            $subjectId = (int) ($gene['subject_id'] ?? 0);
            $type = (string) ($gene['type'] ?? 'lecture');

            if ($subjectId <= 0 || !in_array($type, ['lecture', 'lab'], true)) {
                continue;
            }

            if (!isset($scheduledHours[$subjectId])) {
                $scheduledHours[$subjectId] = ['lecture' => 0.0, 'lab' => 0.0];
            }

            $scheduledHours[$subjectId][$type] += (float) ($gene['duration'] ?? 0);
        }

        $penalty = 0;

        foreach ($this->cachedSubjects as $subject) {
            $subjectHours = $scheduledHours[$subject->id] ?? ['lecture' => 0.0, 'lab' => 0.0];
            $requiredLecture = (float) ($subject->lecture_hours ?? 0);
            $requiredLab = (float) ($subject->lab_hours ?? 0);

            if ($requiredLecture > $subjectHours['lecture']) {
                $penalty += (int) round(($requiredLecture - $subjectHours['lecture']) * $this->missingHoursPenaltyPerHour);
            }

            if ($requiredLab > $subjectHours['lab']) {
                $penalty += (int) round(($requiredLab - $subjectHours['lab']) * $this->missingHoursPenaltyPerHour);
            }
        }

        return $penalty;
    }

    /**
     * Select parent using tournament selection
     */
    protected function selectParent(array $population): array
    {
        $tournamentSize = 5;
        $tournament     = [];

        for ($i = 0; $i < $tournamentSize; $i++) {
            $tournament[] = $population[array_rand($population)];
        }

        usort($tournament, fn($a, $b) => $b['fitness'] <=> $a['fitness']);

        return $tournament[0];
    }

    /**
     * Perform crossover between two parents
     */
    protected function crossover(array $parent1, array $parent2): array
    {
        if (rand(0, 100) / 100 > $this->crossoverRate) {
            return $parent1;
        }

        $genes1 = $parent1['genes'];
        $genes2 = $parent2['genes'];

        $minLen         = min(count($genes1), count($genes2));
        $crossoverPoint = $minLen > 1 ? rand(1, $minLen - 1) : 0;

        $offspringGenes = array_merge(
            array_slice($genes1, 0, $crossoverPoint),
            array_slice($genes2, $crossoverPoint)
        );

        return [
            'genes'        => $offspringGenes,
            'fitness'      => 0,
            'faculty_loads'=> $this->recalculateFacultyLoads($offspringGenes),
        ];
    }

    /**
     * Mutate a chromosome
     */
    protected function mutate(array &$chromosome, Collection $instructors, Collection $rooms): void
    {
        foreach ($chromosome['genes'] as &$gene) {
            if (rand(0, 100) / 100 >= $this->mutationRate) {
                continue;
            }

            $mutationType = rand(0, 3);

            switch ($mutationType) {
                case 0: // Change instructor
                    if ($instructors->isNotEmpty()) {
                        $gene['instructor_id'] = $instructors->random()->id;
                    }
                    break;

                case 1: // Change room
                    $pool = $this->getEligibleRooms($rooms, $gene['type'] ?? 'lecture');

                    if ($pool->isNotEmpty()) {
                        $gene['room_id'] = $pool->random()->id;
                    }
                    break;

                case 2: // Change day
                    if ($this->isNstpGene($gene)) {
                        $allowedDays = ['Saturday'];
                    } else {
                        $allowedDays = array_values(array_filter($this->workingDays, fn ($day) => $day !== 'Saturday'));
                    }

                    if (!empty($allowedDays)) {
                        $gene['day'] = $allowedDays[array_rand($allowedDays)];
                    }
                    break;

                case 3: // Change time slot
                    $instructor      = $this->cachedInstructors[$gene['instructor_id']] ?? null;
                    $durationMinutes = (int) round($gene['duration'] * 60);
                    $subject = $this->cachedSubjects[(int) ($gene['subject_id'] ?? 0)] ?? null;
                    $slots = $subject
                        ? $this->getCandidateTimeSlots(
                            $subject,
                            $durationMinutes,
                            $instructor?->daily_scheme_start ?? null,
                            $instructor?->daily_scheme_end   ?? null
                        )
                        : [];

                    if (!empty($slots)) {
                        $slot              = $slots[array_rand($slots)];
                        $gene['start_time'] = $slot['start'];
                        $gene['end_time']   = $slot['end'];
                    }
                    break;
            }
        }

        $chromosome['faculty_loads'] = $this->recalculateFacultyLoads($chromosome['genes']);
    }

    protected function repairChromosome(
        array &$chromosome,
        Collection $subjects,
        Collection $instructors,
        Collection $rooms,
        string $section
    ): void {
        $existingGenes = $chromosome['genes'];
        $facultyLoads = $this->recalculateFacultyLoads($existingGenes);

        $scheduledHours = [];

        foreach ($existingGenes as $gene) {
            $subjectId = (int) ($gene['subject_id'] ?? 0);
            $type = (string) ($gene['type'] ?? 'lecture');

            if ($subjectId <= 0 || !in_array($type, ['lecture', 'lab'], true)) {
                continue;
            }

            if (!isset($scheduledHours[$subjectId])) {
                $scheduledHours[$subjectId] = ['lecture' => 0.0, 'lab' => 0.0];
            }

            $scheduledHours[$subjectId][$type] += (float) ($gene['duration'] ?? 0);
        }

        foreach ($subjects as $subject) {
            $subjectHours = $scheduledHours[$subject->id] ?? ['lecture' => 0.0, 'lab' => 0.0];

            $requiredLecture = max(0.0, (float) ($subject->lecture_hours ?? 0) - $subjectHours['lecture']);
            if ($requiredLecture > 0.0) {
                $lectureRooms = $this->getEligibleRooms($rooms, 'lecture');
                $newLectureGenes = $this->createGenesForSubject(
                    $subject,
                    'lecture',
                    $requiredLecture,
                    $instructors,
                    $lectureRooms,
                    $section,
                    $existingGenes,
                    $facultyLoads
                );

                foreach ($newLectureGenes as $gene) {
                    $scheduledHours[$subject->id]['lecture'] = ($scheduledHours[$subject->id]['lecture'] ?? 0.0) + (float) $gene['duration'];
                }
            }

            $requiredLab = max(0.0, (float) ($subject->lab_hours ?? 0) - ($scheduledHours[$subject->id]['lab'] ?? 0.0));
            if ($requiredLab > 0.0) {
                $labRooms = $this->getEligibleRooms($rooms, 'lab');
                $newLabGenes = $this->createGenesForSubject(
                    $subject,
                    'lab',
                    $requiredLab,
                    $instructors,
                    $labRooms,
                    $section,
                    $existingGenes,
                    $facultyLoads
                );

                foreach ($newLabGenes as $gene) {
                    $scheduledHours[$subject->id]['lab'] = ($scheduledHours[$subject->id]['lab'] ?? 0.0) + (float) $gene['duration'];
                }
            }
        }

        $chromosome['genes'] = $existingGenes;
        $chromosome['faculty_loads'] = $facultyLoads;
    }

    /**
     * Recalculate faculty loads from genes
     */
    protected function recalculateFacultyLoads(array $genes): array
    {
        $loads = [];

        foreach ($genes as $gene) {
            $id = $gene['instructor_id'];
            if (!isset($loads[$id])) {
                $loads[$id] = ['lecture' => 0, 'lab' => 0];
            }
            $loads[$id][$gene['type']] += $gene['duration'];
        }

        return $loads;
    }

    /**
     * Main evolution loop
     */
    public function evolve(
        Collection $subjects,
        Collection $instructors,
        Collection $rooms,
        string $section,
        int $yearLevel,
        string $semester,
        callable $progressCallback = null
    ): array {
        // Cache instructors on instance so calculateFitness never hits the DB
        $this->cachedInstructors = $instructors->keyBy('id');
        $this->cachedSubjects = $subjects->keyBy('id');

        $population = $this->createInitialPopulation($subjects, $instructors, $rooms, $section, $yearLevel, $semester);

        foreach ($population as &$chromosome) {
            $this->repairChromosome($chromosome, $subjects, $instructors, $rooms, $section);
        }
        unset($chromosome);

        $this->evaluatePopulation($population, $instructors);

        $bestChromosome = $population[0];

        for ($generation = 0; $generation < $this->generations; $generation++) {
            $newPopulation = [];

            // Elitism
            for ($i = 0; $i < min($this->eliteSize, count($population)); $i++) {
                $newPopulation[] = $population[$i];
            }

            // Breed new offspring
            while (count($newPopulation) < $this->populationSize) {
                $parent1  = $this->selectParent($population);
                $parent2  = $this->selectParent($population);
                $offspring = $this->crossover($parent1, $parent2);
                $this->mutate($offspring, $instructors, $rooms);
                $this->repairChromosome($offspring, $subjects, $instructors, $rooms, $section);
                $newPopulation[] = $offspring;
            }

            $population = $newPopulation;
            $this->evaluatePopulation($population, $instructors);

            if ($population[0]['fitness'] > $bestChromosome['fitness']) {
                $bestChromosome = $population[0];
            }

            if ($progressCallback) {
                $progressCallback($generation + 1, $this->generations, $bestChromosome['fitness']);
            }
        }

        return $bestChromosome;
    }
}