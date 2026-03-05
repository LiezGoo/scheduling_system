<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Models\AcademicYear;
use App\Models\Department;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'created_by',
        'academic_year',
        'semester',
        'year_level',
        'block',
        'status',
        'ga_parameters',
        'fitness_score',
    ];

    protected $casts = [
        'ga_parameters' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status Constants - Now represents generation status, not approval
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_GENERATED = 'GENERATED';
    const STATUS_FINALIZED = 'FINALIZED';

    /**
     * Get the program this schedule belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the academic year this schedule belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year', 'name');
    }

    /**
     * Get the department this schedule belongs to (via program).
     */
    public function department(): HasOneThrough
    {
        return $this->hasOneThrough(
            Department::class,
            Program::class,
            'id',
            'id',
            'program_id',
            'department_id'
        );
    }

    /**
     * Get the user who created this schedule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all schedule items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
    }

    /**
     * Get all adjustment requests for this schedule
     */
    public function adjustmentRequests(): HasMany
    {
        return $this->hasMany(ScheduleAdjustmentRequest::class);
    }

    /**
     * Get pending adjustment requests
     */
    public function pendingAdjustments(): HasMany
    {
        return $this->adjustmentRequests()->where('status', ScheduleAdjustmentRequest::STATUS_PENDING);
    }

    /**
     * Scope to filter by program
     */
    public function scopeForProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for finalized schedules
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    /**
     * Check if schedule is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if schedule is generated
     */
    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    /**
     * Check if schedule is finalized
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Finalize the schedule - lock it for editing
     */
    public function finalize(): bool
    {
        if (!$this->isGenerated() && !$this->isDraft()) {
            return false;
        }

        $this->status = self::STATUS_FINALIZED;
        return $this->save();
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_GENERATED => 'bg-info',
            self::STATUS_FINALIZED => 'bg-success',
            default => 'bg-secondary',
        };
    }

    /**
     * Human-friendly status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_FINALIZED => 'Finalized',
            default => $this->status,
        };
    }
}