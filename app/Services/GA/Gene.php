<?php

namespace App\Services\GA;

use App\Models\Subject;

class Gene
{
    public int $subject_id;
    public string $subject_code;
    public int $instructor_id;
    public int $room_id;
    public string $day;
    public string $start_time;
    public string $end_time;
    public string $section;
    public string $type; // lecture or lab
    public float $duration;

    public function __construct(array $data)
    {
        $this->subject_id = $data['subject_id'];
        $this->subject_code = $data['subject_code'];
        $this->instructor_id = $data['instructor_id'];
        $this->room_id = $data['room_id'];
        $this->day = $data['day'];
        $this->start_time = $data['start_time'];
        $this->end_time = $data['end_time'];
        $this->section = $data['section'];
        $this->type = $data['type'];
        $this->duration = $data['duration'];
    }

    public function toArray(): array
    {
        return [
            'subject_id' => $this->subject_id,
            'subject_code' => $this->subject_code,
            'instructor_id' => $this->instructor_id,
            'room_id' => $this->room_id,
            'day' => $this->day,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'section' => $this->section,
            'type' => $this->type,
            'duration' => $this->duration,
        ];
    }
}
