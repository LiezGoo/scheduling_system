<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Models\Department;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'created_by',
        'approved_by_program_head',
        'approved_by_department_head',
        'academic_year',
        'semester',
        'year_level',
        'block',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_remarks',
        'program_head_approved_at',
        'department_head_approved_at',
        'program_head_remarks',
        'department_head_remarks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'program_head_approved_at' => 'datetime',
        'department_head_approved_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_DRAFT = 'DRAFT';
    const STATUS_PENDING_APPROVAL = 'PENDING_APPROVAL';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';

    // Backward-compatible alias
    const STATUS_PENDING = self::STATUS_PENDING_APPROVAL;

    /**
     * Get the program this schedule belongs to
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
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
     * Get the program head who approved this schedule
     */
    public function programHeadApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_program_head');
    }

    /**
     * Get the department head who approved this schedule
     */
    public function departmentHeadApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_department_head');
    }

    /**
     * Get the user who reviewed this schedule.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get all schedule items
     */
    public function items(): HasMany
    {
        return $this->hasMany(ScheduleItem::class);
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
     * Scope for pending schedules
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING_APPROVAL);
    }

    /**
     * Scope for approved schedules
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if schedule is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if schedule is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if schedule is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if schedule is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Submit schedule for approval
     */
    public function submit(): bool
    {
        if (!($this->isDraft() || $this->isRejected())) {
            return false;
        }

        $this->status = self::STATUS_PENDING_APPROVAL;
        $this->submitted_at = now();
        $this->reviewed_at = null;
        $this->reviewed_by = null;
        $this->review_remarks = null;
        $this->approved_by_department_head = null;
        $this->department_head_approved_at = null;
        $this->department_head_remarks = null;
        return $this->save();
    }

    /**
     * Approve schedule by department head
     */
    public function approveByDepartmentHead(User $departmentHead, ?string $remarks = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $departmentHead->id;
        $this->reviewed_at = now();
        $this->review_remarks = $remarks;
        $this->approved_by_department_head = $departmentHead->id;
        $this->department_head_approved_at = now();
        $this->department_head_remarks = $remarks;

        return $this->save();
    }

    /**
     * Reject schedule by department head
     */
    public function rejectByDepartmentHead(User $departmentHead, string $remarks): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_REJECTED;
        $this->reviewed_by = $departmentHead->id;
        $this->reviewed_at = now();
        $this->review_remarks = $remarks;
        $this->approved_by_department_head = $departmentHead->id;
        $this->department_head_approved_at = now();
        $this->department_head_remarks = $remarks;

        return $this->save();
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_PENDING_APPROVAL => 'bg-warning',
            self::STATUS_APPROVED => 'bg-success',
            self::STATUS_REJECTED => 'bg-danger',
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
            self::STATUS_PENDING_APPROVAL => 'Pending Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => $this->status,
        };
    }
}
