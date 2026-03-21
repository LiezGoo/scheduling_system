<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use App\Models\Room;
use Illuminate\Support\Collection;
use Exception;

class ScheduleDataLoader
{
    /**
     * Load subjects for program, year level, and semester.
     */
    public function loadSubjects(int $programId, int $yearLevel, string $semester, ?int $departmentId = null): Collection
    {
        $semesterCandidates = $this->normalizeSemesterCandidates($semester);

        $subjects = Subject::whereHas('programs', function ($query) use ($programId, $yearLevel, $semesterCandidates) {
            $query->where('program_id', $programId)
                  ->where('year_level', $yearLevel)
                  ->whereIn('semester', $semesterCandidates);
        })
        ->where('is_active', true)
        ->with('programs')
        ->get();

        if ($subjects->isEmpty() && $departmentId !== null) {
            // Fallback: fetch subjects in the program's department if curriculum mapping not found
            $subjects = Subject::where('department_id', $departmentId)
                ->where('is_active', true)
                ->get();
        }

        if ($subjects->isEmpty()) {
            throw new Exception('No subjects found for the specified program, year level, and semester.');
        }

        return $subjects;
    }

    /**
     * Load available instructors from department.
     */
    public function loadInstructors(int $departmentId): Collection
    {
        $instructors = User::where('department_id', $departmentId)
            ->whereIn('role', [User::ROLE_INSTRUCTOR, User::ROLE_DEPARTMENT_HEAD, User::ROLE_PROGRAM_HEAD])
            ->where('is_active', true)
            ->whereNotNull('daily_scheme_start')
            ->whereNotNull('daily_scheme_end')
            ->get();

        if ($instructors->isEmpty()) {
            throw new Exception('No instructors available in the department with configured teaching schemes.');
        }

        return $instructors;
    }

    /**
     * Load available rooms.
     */
    public function loadRooms(): Collection
    {
        $rooms = Room::all();

        if ($rooms->isEmpty()) {
            throw new Exception('No rooms available for scheduling.');
        }

        return $rooms;
    }

    /**
     * Normalize semester values to match program_subjects canonical entries.
     */
    protected function normalizeSemesterCandidates(string $semester): array
    {
        $normalized = strtolower(trim($semester));
        $fallback = [];

        // 1st Semester => 1, 2nd Semester => 2, etc
        if (preg_match('/^(\d+)(st|nd|rd|th)?\s*semester$/i', $normalized, $matches)) {
            $fallback[] = (string) $matches[1];
            $fallback[] = strtolower($matches[1] . (isset($matches[2]) ? $matches[2] : ''));
        }

        // 1st, 2nd, 3rd, summer direct variants
        if (preg_match('/^(\d+)(st|nd|rd|th)$/i', $normalized, $matches)) {
            $fallback[] = (string) $matches[1];
            $fallback[] = strtolower($matches[0]);
        }

        // numeric strings
        if (ctype_digit($normalized)) {
            $fallback[] = $normalized;
        }

        if ($normalized === 'summer') {
            $fallback[] = 'summer';
        }

        // include original value too
        $fallback[] = $normalized;

        return array_unique(array_filter($fallback, fn($value) => trim((string)$value) !== ''));
    }
}
