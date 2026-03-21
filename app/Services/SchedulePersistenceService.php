<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleItem;
use Illuminate\Support\Facades\DB;

class SchedulePersistenceService
{
    /**
     * Create a new schedule record.
     */
    public function createSchedule(array $data): Schedule
    {
        return Schedule::create($data);
    }

    /**
     * Save schedule items (genes) to database.
     */
    public function saveItems(Schedule $schedule, array $genes): void
    {
        DB::transaction(function () use ($schedule, $genes) {
            // Clear existing items
            $schedule->items()->delete();

            $scheduleItems = [];
            $now = now();

            foreach ($genes as $gene) {
                // Handle both object-based genes and array-based genes (for backward compatibility during migration)
                $geneData = is_array($gene) ? $gene : $gene->toArray();

                $scheduleItems[] = [
                    'schedule_id' => $schedule->id,
                    'subject_id' => $geneData['subject_id'],
                    'instructor_id' => $geneData['instructor_id'],
                    'room_id' => $geneData['room_id'],
                    'day_of_week' => $geneData['day'] ?? $geneData['day_of_week'],
                    'start_time' => $geneData['start_time'],
                    'end_time' => $geneData['end_time'],
                    'section' => $geneData['section'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Batch insert for performance
            if (!empty($scheduleItems)) {
                ScheduleItem::insert($scheduleItems);
            }
        });
    }

    /**
     * Update schedule progress.
     */
    public function updateProgress(Schedule $schedule, int $currentGen, int $totalGen, float $fitness): void
    {
        $schedule->update([
            'ga_progress' => [
                'current_generation' => $currentGen,
                'total_generations' => $totalGen,
                'best_fitness' => $fitness,
            ],
        ]);
    }

    /**
     * Update schedule status and fitness score.
     */
    public function completeSchedule(Schedule $schedule, float $fitnessScore, string $status = Schedule::STATUS_DRAFT): void
    {
        $schedule->update([
            'status' => $status,
            'fitness_score' => $fitnessScore,
        ]);
    }

    /**
     * Record failure in schedule.
     */
    public function recordFailure(Schedule $schedule, string $errorMessage): void
    {
        // Use DRAFT status on failure — 'failed' is not a valid enum value and would cause a DB error.
        $schedule->update([
            'status' => Schedule::STATUS_DRAFT,
        ]);
    }
}
