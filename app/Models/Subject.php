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
        'department_id',
        'created_by',
        'units',
        'lecture_hours',
        'lab_hours',
        'year_level',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'units' => 'decimal:1',
        'lecture_hours' => 'decimal:1',
        'lab_hours' => 'decimal:1',
        'year_level' => 'integer',
        'semester' => 'integer',
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
     * Get the year level label.
     */
    public function getYearLevelLabelAttribute()
    {
        return match($this->year_level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $this->year_level . 'th Year',
        };
    }

    /**
     * Get the semester label.
     */
    public function getSemesterLabelAttribute()
    {
        return match($this->semester) {
            1 => '1st Semester',
            2 => '2nd Semester',
            default => $this->semester . ' Semester',
        };
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
                    ->where('users.role', '!=', 'admin');
    }

    /**
     * Get all faculty assignments for this subject with load constraints.
     */
    public function getFacultyWithConstraints()
    {
        return $this->facultyInstructors()->get();
    }
}
