<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacultyWorkloadConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'program_id',
        'contract_type',
        'max_lecture_hours',
        'max_lab_hours',
        'max_hours_per_day',
        'available_days', // JSON: ['Monday', 'Tuesday', ...]
        'teaching_scheme', // JSON: { Monday: { start, end }, ... }
        'start_time',
        'end_time',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'available_days' => 'array',
        'teaching_scheme' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: Faculty (User)
     */
    public function faculty()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: Program
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relationship: Creator (User who created this config)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Active configurations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: For a specific program
     */
    public function scopeForProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope: Search by faculty name
     */
    public function scopeSearchFaculty($query, $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->whereHas('faculty', function ($q) use ($search) {
            $q->where(function ($facultyQuery) use ($search) {
                $facultyQuery->where('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        });
    }

    /**
     * Scope: Filter by contract type
     */
    public function scopeByContractType($query, $contractType)
    {
        if (!$contractType) {
            return $query;
        }

        return $query->where('contract_type', $contractType);
    }

    /**
     * Scope: Filter by department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        if (!$departmentId) {
            return $query;
        }

        return $query->whereHas('program', function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Get available days as comma-separated string
     */
    public function getAvailableDaysStringAttribute()
    {
        if (!$this->available_days) {
            return 'N/A';
        }

        $dayMap = [
            'Monday' => 'Mon',
            'Tuesday' => 'Tue',
            'Wednesday' => 'Wed',
            'Thursday' => 'Thu',
            'Friday' => 'Fri',
            'Saturday' => 'Sat',
            'Sunday' => 'Sun',
        ];

        $shortDays = array_map(function ($day) use ($dayMap) {
            return $dayMap[$day] ?? $day;
        }, $this->available_days);

        return implode(', ', $shortDays);
    }

    /**
     * Format time for display
     */
    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time ? date('g:i A', strtotime($this->start_time)) : 'N/A';
    }

    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time ? date('g:i A', strtotime($this->end_time)) : 'N/A';
    }
}
