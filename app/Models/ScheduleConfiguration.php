<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'academic_year_id',
        'semester',
        'year_level',
        'number_of_blocks',
        'department_head_id',
    ];

    /**
     * Relationship: Program
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Relationship: Academic year
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Relationship: Department head (User)
     */
    public function departmentHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'department_head_id');
    }
}
