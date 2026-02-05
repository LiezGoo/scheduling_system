<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'subject_id',
        'instructor_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
        'section',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the schedule this item belongs to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Get the subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the instructor
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Get the room
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Check if this item conflicts with another schedule item
     */
    public function conflictsWith(ScheduleItem $other): bool
    {
        // Must be same day
        if ($this->day_of_week !== $other->day_of_week) {
            return false;
        }

        // Check time overlap
        $thisStart = strtotime($this->start_time);
        $thisEnd = strtotime($this->end_time);
        $otherStart = strtotime($other->start_time);
        $otherEnd = strtotime($other->end_time);

        return ($thisStart < $otherEnd && $thisEnd > $otherStart);
    }

    /**
     * Check for instructor conflicts
     */
    public static function hasInstructorConflict($instructorId, $dayOfWeek, $startTime, $endTime, $excludeScheduleId = null)
    {
        $query = static::where('instructor_id', $instructorId)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('schedule', function($q) {
                $q->whereIn('status', [Schedule::STATUS_PENDING_APPROVAL, Schedule::STATUS_APPROVED]);
            });

        if ($excludeScheduleId) {
            $query->where('schedule_id', '!=', $excludeScheduleId);
        }

        $existingItems = $query->get();

        foreach ($existingItems as $item) {
            $existingStart = strtotime($item->start_time);
            $existingEnd = strtotime($item->end_time);
            $newStart = strtotime($startTime);
            $newEnd = strtotime($endTime);

            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for room conflicts
     */
    public static function hasRoomConflict($roomId, $dayOfWeek, $startTime, $endTime, $excludeScheduleId = null)
    {
        $query = static::where('room_id', $roomId)
            ->where('day_of_week', $dayOfWeek)
            ->whereHas('schedule', function($q) {
                $q->whereIn('status', [Schedule::STATUS_PENDING_APPROVAL, Schedule::STATUS_APPROVED]);
            });

        if ($excludeScheduleId) {
            $query->where('schedule_id', '!=', $excludeScheduleId);
        }

        $existingItems = $query->get();

        foreach ($existingItems as $item) {
            $existingStart = strtotime($item->start_time);
            $existingEnd = strtotime($item->end_time);
            $newStart = strtotime($startTime);
            $newEnd = strtotime($endTime);

            if ($newStart < $existingEnd && $newEnd > $existingStart) {
                return true;
            }
        }

        return false;
    }
}
