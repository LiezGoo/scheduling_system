<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorLoad extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'program_id',
        'subject_id',
        'academic_year_id',
        'semester',
        'year_level',
        'block_section',
        'lec_hours',
        'lab_hours',
        'total_hours',
    ];

    protected $casts = [
        'year_level' => 'integer',
        'lec_hours' => 'integer',
        'lab_hours' => 'integer',
        'total_hours' => 'integer',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
