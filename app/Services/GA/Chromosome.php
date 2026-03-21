<?php

namespace App\Services\GA;

use App\Services\ConstraintValidator;
use Illuminate\Support\Collection;

class Chromosome
{
    /** @var Gene[] */
    public array $genes = [];
    public float $fitness = 0;
    public array $faculty_loads = [];

    public function __construct(array $genes = [])
    {
        $this->genes = $genes;
        $this->calculateFacultyLoads();
    }

    /**
     * Calculate faculty loads from current genes.
     */
    public function calculateFacultyLoads(): void
    {
        $this->faculty_loads = [];
        foreach ($this->genes as $gene) {
            $instructorId = $gene->instructor_id;
            if (!isset($this->faculty_loads[$instructorId])) {
                $this->faculty_loads[$instructorId] = ['lecture' => 0, 'lab' => 0];
            }
            $this->faculty_loads[$instructorId][$gene->type] += $gene->duration;
        }
    }

    /**
     * Calculate fitness score for this chromosome.
     */
    public function calculateFitness(ConstraintValidator $validator, Collection $instructors, array $workingDays): float
    {
        $baseScore = 1000;
        $penalty = 0;
        $geneCollection = collect(array_map(fn($g) => $g->toArray(), $this->genes));

        foreach ($this->genes as $index => $gene) {
            // Get genes before this one to check conflicts (using array for validator compatibility)
            $existingGenes = collect(array_slice($geneCollection->toArray(), 0, $index));
            
            $penalty += $validator->calculateGenePenalty(
                $gene->toArray(),
                $existingGenes,
                $this->faculty_loads,
                $instructors
            );
        }

        // Check faculty load for all instructors
        foreach ($this->faculty_loads as $instructorId => $loads) {
            $instructor = $instructors->firstWhere('id', $instructorId);
            if ($instructor) {
                $loadValidation = $validator->validateFacultyLoad(
                    $instructor,
                    $loads['lecture'],
                    $loads['lab']
                );
                $penalty += $loadValidation['penalty'];
            }
        }

        // Check break violations
        foreach ($this->faculty_loads as $instructorId => $loads) {
            $instructor = $instructors->firstWhere('id', $instructorId);
            if ($instructor) {
                foreach ($workingDays as $day) {
                    $breakCheck = $validator->hasBreakConflict($instructor, $geneCollection, $day);
                    $penalty += $breakCheck['penalty'];
                }
            }
        }

        $this->fitness = max(0, $baseScore - $penalty);
        return $this->fitness;
    }

    public function toArray(): array
    {
        return [
            'genes' => array_map(fn($g) => $g->toArray(), $this->genes),
            'fitness' => $this->fitness,
            'faculty_loads' => $this->faculty_loads,
        ];
    }
}
