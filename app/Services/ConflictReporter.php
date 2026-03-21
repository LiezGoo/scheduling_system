<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConflictReporter
{
    protected ConstraintValidator $validator;

    public function __construct(ConstraintValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Comprehensive validation of generated schedule.
     * Returns detailed conflict report.
     */
    public function generateReport(Schedule $schedule): array
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
        
        // 1. Check room conflicts
        foreach ($items as $item) {
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

        // 2. Check instructor time conflicts and collect faculty loads
        foreach ($items as $item) {
            $instructorItems = $items->where('instructor_id', $item->instructor_id)
                ->where('day_of_week', $item->day_of_week)
                ->where('id', '!=', $item->id);

            foreach ($instructorItems as $other) {
                if ($this->timesOverlap($item->start_time, $item->end_time, $other->start_time, $other->end_time)) {
                    $report['instructor_conflicts']++;
                    $report['conflicts_detail'][] = [
                        'type' => 'instructor_conflict',
                        'instructor' => ($item->instructor->first_name ?? '') . ' ' . ($item->instructor->last_name ?? ''),
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
                        'instructor' => ($instructor->first_name ?? '') . ' ' . ($instructor->last_name ?? ''),
                        'violations' => $loadValidation['violations'],
                    ];
                }
            }
        }

        // 4. Check scheme violations
        foreach ($items as $item) {
            if ($item->instructor && !$this->validator->isWithinInstructorScheme($item->instructor, $item->start_time, $item->end_time, $item->day_of_week, $schedule->program_id)) {
                $allowedRange = $this->validator->getAllowedRangeForDay($item->instructor_id, $item->day_of_week, $schedule->program_id);

                $report['scheme_violations']++;
                $report['conflicts_detail'][] = [
                    'type' => 'scheme_violation',
                    'instructor' => ($item->instructor->first_name ?? '') . ' ' . ($item->instructor->last_name ?? ''),
                    'day' => $item->day_of_week,
                    'scheduled_time' => $item->start_time . '-' . $item->end_time,
                    'allowed_range' => $allowedRange
                        ? ($allowedRange['start'] . '-' . $allowedRange['end'])
                        : 'Not configured',
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
                                'instructor' => ($current->instructor->first_name ?? '') . ' ' . ($current->instructor->last_name ?? ''),
                                'day' => $day,
                                'consecutive_hours' => round($totalHours, 2),
                            ];
                        }
                    }
                }
            }
        }

        // 6. Check section time conflict
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
     * Check if two time slots overlap.
     */
    protected function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $s1 = Carbon::parse($start1);
        $e1 = Carbon::parse($end1);
        $s2 = Carbon::parse($start2);
        $e2 = Carbon::parse($end2);

        return $s1->lessThan($e2) && $s2->lessThan($e1);
    }
}
