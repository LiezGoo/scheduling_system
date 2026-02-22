<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $fillable = [
        'subject_code',
        'subject_name',
        'description',
        'department_id',
        'created_by',
        'units',
        'lecture_hours',
        'lab_hours',
        'is_active',
    ];

    protected $casts = [
        'units' => 'decimal:1',
        'lecture_hours' => 'decimal:1',
        'lab_hours' => 'decimal:1',
        'is_active' => 'boolean',
    ];

    /**
     * Get the department that owns the subject.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created the subject.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Programs that include this subject in their curriculum.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'program_subjects')
            ->withPivot(['year_level', 'semester'])
            ->withTimestamps();
    }

    /**
     * Get the computed subject type based on lecture/lab hours.
     */
    public function getComputedTypeAttribute(): ?string
    {
        $lectureHours = (float) $this->lecture_hours;
        $labHours = (float) $this->lab_hours;

        if ($lectureHours > 0 && $labHours > 0) {
            return 'Lecture & Laboratory';
        }

        if ($lectureHours > 0) {
            return 'Lecture';
        }

        if ($labHours > 0) {
            return 'Laboratory';
        }

        return null;
    }

    /**
     * Scope to active subjects only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to a specific department.
     */
    public function scopeForDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get all eligible instructors assigned to teach this subject.
     */
    public function facultyInstructors()
    {
        return $this->belongsToMany(User::class, 'faculty_subjects')
                    ->withPivot('max_sections', 'max_load_units')
                    ->withTimestamps()
                    ->whereIn('users.role', [
                        User::ROLE_INSTRUCTOR,
                        User::ROLE_PROGRAM_HEAD,
                        User::ROLE_DEPARTMENT_HEAD,
                    ]);
    }

    /**
     * Get all faculty assignments for this subject with load constraints.
     */
    public function getFacultyWithConstraints()
    {
        return $this->facultyInstructors()->get();
    }
}
