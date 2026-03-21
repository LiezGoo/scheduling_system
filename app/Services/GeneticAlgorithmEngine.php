<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subject;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * GeneticAlgorithmEngine
 * 
 * Core genetic algorithm implementation for automated schedule generation.
 * Handles population management, evolution, and constraint-aware optimization.
 */
class GeneticAlgorithmEngine
{
    protected const FITNESS_BASE_SCORE = 10000;
    protected const HARD_CONFLICT_PENALTY_WEIGHT = 1000;
    protected const OVERLOAD_HOUR_PENALTY_WEIGHT = 200;
    protected const SOFT_PENALTY_WEIGHT = 50;

    protected ConstraintValidator $validator;
    protected int $populationSize;
    protected int $generations;
    protected float $mutationRate;
    protected float $crossoverRate;
    protected int $eliteSize;
    
    // Working days
    protected array $workingDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    // Global time window
    protected string $globalStartTime = '07:00';
    protected string $globalEndTime = '19:00';

    public function __construct(
        int $populationSize = 50,
        int $generations = 100,
        float $mutationRate = 0.15,
        float $crossoverRate = 0.80,
        int $eliteSize = 5
    ) {
        $this->validator = new ConstraintValidator();
        $this->populationSize = $populationSize;
        $this->generations = $generations;
        $this->mutationRate = $mutationRate;
        $this->crossoverRate = $crossoverRate;
        $this->eliteSize = $eliteSize;
    }

    /**
     * Generate time slots dynamically based on duration
     */
    public function generateTimeSlots(int $durationMinutes = 60, ?string $instructorSchemeStart = null, ?string $instructorSchemeEnd = null): array
    {
        $start = $instructorSchemeStart ?? $this->globalStartTime;
        $end = $instructorSchemeEnd ?? $this->globalEndTime;

        $startTime = Carbon::parse($start);
        $endTime = Carbon::parse($end);
        
        $slots = [];
        $period = CarbonPeriod::create($startTime, "{$durationMinutes} minutes", $endTime);

        foreach ($period as $time) {
            $slotEnd = $time->copy()->addMinutes($durationMinutes);
            
            // Ensure slot doesn't exceed end time
            if ($slotEnd->lessThanOrEqualTo($endTime)) {
                $slots[] = [
                    'start' => $time->format('H:i'),
                    'end' => $slotEnd->format('H:i'),
                ];
            }
        }

        return $slots;
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
            $chromosome = $this->createChromosome($subjects, $instructors, $rooms, $section);
            $population[] = $chromosome;
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
        $genes = [];
        $facultyLoads = [];

        foreach ($subjects as $subject) {
            // Handle lecture hours
            if ($subject->lecture_hours > 0) {
                $lectureGenes = $this->createGenesForSubject(
                    $subject,
                    'lecture',
                    $subject->lecture_hours,
                    $instructors,
                    $rooms,
                    $section,
                    $genes,
                    $facultyLoads
                );
                $genes = array_merge($genes, $lectureGenes);
            }

            // Handle lab hours
            if ($subject->lab_hours > 0) {
                $labGenes = $this->createGenesForSubject(
                    $subject,
                    'lab',
                    $subject->lab_hours,
                    $instructors,
                    $rooms->where('room_type', 'Laboratory'),
                    $section,
                    $genes,
                    $facultyLoads
                );
                $genes = array_merge($genes, $labGenes);
            }
        }

        return [
            'genes' => $genes,
            'fitness' => 0,
            'faculty_loads' => $facultyLoads,
        ];
    }

    /**
     * Create genes for a specific subject (may span multiple days/time slots)
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
        $genes = [];
        $remainingHours = $hours;
        $maxAttempts = 50;

        while ($remainingHours > 0 && $maxAttempts > 0) {
            $maxAttempts--;

            // Determine duration for this gene (1, 2, or 3 hours)
            $duration = min($remainingHours, $this->selectDuration($type));
            $durationMinutes = $duration * 60;

            // Random instructor
            $instructor = $instructors->random();

            // Generate time slots for this instructor
            $timeSlots = $this->generateTimeSlots(
                $durationMinutes,
                $instructor->daily_scheme_start,
                $instructor->daily_scheme_end
            );

            if (empty($timeSlots)) {
                continue; // No valid time slots for this instructor
            }

            // Random time slot
            $timeSlot = $timeSlots[array_rand($timeSlots)];

            // Random day
            $day = $this->workingDays[array_rand($this->workingDays)];

            // Random room
            $room = $rooms->random();

            // Create gene
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

            // Check basic constraints
            $existingCollection = collect($existingGenes);
            
            // Check for hard conflicts
            if (!$this->validator->checkRoomAvailability($room->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue; // Room conflict, try again
            }

            if ($this->validator->checkInstructorTimeConflict($instructor->id, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue; // Instructor conflict, try again
            }

            if ($this->validator->checkSectionTimeConflict($section, $day, $timeSlot['start'], $timeSlot['end'], $existingCollection)) {
                continue; // Section conflict, try again
            }

            // Update faculty load tracking
            if (!isset($facultyLoads[$instructor->id])) {
                $facultyLoads[$instructor->id] = ['lecture' => 0, 'lab' => 0];
            }

            $facultyLoads[$instructor->id][$type] += $duration;

            // Gene is valid, add it
            $genes[] = $gene;
            $existingGenes[] = $gene;
            $remainingHours -= $duration;
        }

        return $genes;
    }

    /**
     * Select duration for a gene (prefer standard durations)
     */
    protected function selectDuration(string $type): float
    {
        if ($type === 'lab') {
            // Labs typically 2-3 hours
            return [2, 3][array_rand([2, 3])];
        }

        // Lectures typically 1-3 hours
        return [1, 1.5, 2, 3][array_rand([1, 1.5, 2, 3])];
    }

    /**
     * Calculate fitness for entire population
     */
    public function evaluatePopulation(array &$population): void
    {
        foreach ($population as &$chromosome) {
            $chromosome['fitness'] = $this->calculateFitness($chromosome);
        }

        // Sort by fitness (higher is better)
        usort($population, fn($a, $b) => $b['fitness'] <=> $a['fitness']);
    }

    /**
     * Calculate fitness score for a chromosome
     */
    protected function calculateFitness(array $chromosome): float
    {
        $genes = $chromosome['genes'];
        $facultyLoads = $chromosome['faculty_loads'];
        $geneCollection = collect($genes);

        $hardConflicts = $this->countHardConflicts($genes);
        $overloadHours = $this->calculateOverloadHours($facultyLoads);
        $softPenalties = $this->countSoftPenaltyUnits($geneCollection);

        $fitness = self::FITNESS_BASE_SCORE
            - ($hardConflicts * self::HARD_CONFLICT_PENALTY_WEIGHT)
            - ($overloadHours * self::OVERLOAD_HOUR_PENALTY_WEIGHT)
            - ($softPenalties * self::SOFT_PENALTY_WEIGHT);

        return max(0, $fitness);
    }

    protected function countHardConflicts(array $genes): int
    {
        $hardConflicts = 0;
        $geneCount = count($genes);

        for ($i = 0; $i < $geneCount; $i++) {
            for ($j = $i + 1; $j < $geneCount; $j++) {
                $left = $genes[$i];
                $right = $genes[$j];

                if (($left['day'] ?? null) !== ($right['day'] ?? null)) {
                    continue;
                }

                $overlap = $this->validator->timeSlotsOverlap(
                    (string) $left['start_time'],
                    (string) $left['end_time'],
                    (string) $right['start_time'],
                    (string) $right['end_time']
                );

                if (!$overlap) {
                    continue;
                }

                if (($left['room_id'] ?? null) === ($right['room_id'] ?? null)) {
                    $hardConflicts++;
                }

                if (($left['instructor_id'] ?? null) === ($right['instructor_id'] ?? null)) {
                    $hardConflicts++;
                }

                if (($left['section'] ?? null) === ($right['section'] ?? null)) {
                    $hardConflicts++;
                }
            }
        }

        return $hardConflicts;
    }

    protected function calculateOverloadHours(array $facultyLoads): float
    {
        $overloadHours = 0.0;

        foreach ($facultyLoads as $instructorId => $loads) {
            $instructor = User::find((int) $instructorId);
            if (!$instructor) {
                continue;
            }

            $validation = $this->validator->validateFacultyLoad(
                $instructor,
                (float) ($loads['lecture'] ?? 0),
                (float) ($loads['lab'] ?? 0)
            );

            foreach (($validation['violations'] ?? []) as $violation) {
                $overloadHours += (float) ($violation['excess'] ?? 0);
            }
        }

        return $overloadHours;
    }

    protected function countSoftPenaltyUnits(Collection $genes): int
    {
        $units = 0;

        foreach ($genes as $gene) {
            $instructor = User::find((int) ($gene['instructor_id'] ?? 0));
            if (!$instructor) {
                continue;
            }

            if (!$this->validator->isWithinInstructorScheme(
                $instructor,
                (string) ($gene['start_time'] ?? ''),
                (string) ($gene['end_time'] ?? ''),
                (string) ($gene['day'] ?? '')
            )) {
                $units++;
            }
        }

        $instructorIds = array_unique(array_column($genes->all(), 'instructor_id'));
        foreach ($instructorIds as $instructorId) {
            $instructor = User::find((int) $instructorId);
            if (!$instructor) {
                continue;
            }

            foreach ($this->workingDays as $day) {
                $breakCheck = $this->validator->hasBreakConflict($instructor, $genes, $day);
                $units += count($breakCheck['violations'] ?? []);
            }
        }

        return $units;
    }

    /**
     * Select parents for breeding using tournament selection
     */
    protected function selectParent(array $population): array
    {
        $tournamentSize = 5;
        $tournament = [];

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
            return $parent1; // No crossover
        }

        $genes1 = $parent1['genes'];
        $genes2 = $parent2['genes'];

        $crossoverPoint = rand(1, min(count($genes1), count($genes2)) - 1);

        $offspringGenes = array_merge(
            array_slice($genes1, 0, $crossoverPoint),
            array_slice($genes2, $crossoverPoint)
        );

        return [
            'genes' => $offspringGenes,
            'fitness' => 0,
            'faculty_loads' => $this->recalculateFacultyLoads($offspringGenes),
        ];
    }

    /**
     * Mutate a chromosome
     */
    protected function mutate(array &$chromosome, Collection $instructors, Collection $rooms): void
    {
        foreach ($chromosome['genes'] as &$gene) {
            if (rand(0, 100) / 100 < $this->mutationRate) {
                // Randomly mutate one aspect of the gene
                $mutationType = rand(0, 3);

                switch ($mutationType) {
                    case 0: // Change instructor
                        $instructor = $instructors->random();
                        $gene['instructor_id'] = $instructor->id;
                        break;

                    case 1: // Change room
                        $room = $rooms->random();
                        $gene['room_id'] = $room->id;
                        break;

                    case 2: // Change day
                        $gene['day'] = $this->workingDays[array_rand($this->workingDays)];
                        break;

                    case 3: // Change time slot
                        $instructor = User::find($gene['instructor_id']);
                        if ($instructor) {
                            $durationMinutes = $gene['duration'] * 60;
                            $slots = $this->generateTimeSlots(
                                $durationMinutes,
                                $instructor->daily_scheme_start,
                                $instructor->daily_scheme_end
                            );
                            if (!empty($slots)) {
                                $slot = $slots[array_rand($slots)];
                                $gene['start_time'] = $slot['start'];
                                $gene['end_time'] = $slot['end'];
                            }
                        }
                        break;
                }
            }
        }

        // Recalculate faculty loads after mutation
        $chromosome['faculty_loads'] = $this->recalculateFacultyLoads($chromosome['genes']);
    }

    /**
     * Recalculate faculty loads from genes
     */
    protected function recalculateFacultyLoads(array $genes): array
    {
        $loads = [];

        foreach ($genes as $gene) {
            $instructorId = $gene['instructor_id'];
            if (!isset($loads[$instructorId])) {
                $loads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }
            $loads[$instructorId][$gene['type']] += $gene['duration'];
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
        // Create initial population
        $population = $this->createInitialPopulation($subjects, $instructors, $rooms, $section, $yearLevel, $semester);
        
        // Evaluate initial population
        $this->evaluatePopulation($population);

        $bestChromosome = $population[0];

        // Evolution loop
        for ($generation = 0; $generation < $this->generations; $generation++) {
            $newPopulation = [];

            // Elitism: Keep best chromosomes
            for ($i = 0; $i < $this->eliteSize; $i++) {
                $newPopulation[] = $population[$i];
            }

            // Generate new offspring
            while (count($newPopulation) < $this->populationSize) {
                $parent1 = $this->selectParent($population);
                $parent2 = $this->selectParent($population);

                $offspring = $this->crossover($parent1, $parent2);
                $this->mutate($offspring, $instructors, $rooms);

                $newPopulation[] = $offspring;
            }

            $population = $newPopulation;
            $this->evaluatePopulation($population);

            // Track best solution
            if ($population[0]['fitness'] > $bestChromosome['fitness']) {
                $bestChromosome = $population[0];
            }

            // Progress callback
            if ($progressCallback) {
                $progressCallback($generation + 1, $this->generations, $bestChromosome['fitness']);
            }
        }

        return $bestChromosome;
    }
}
