<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'role',
        'status',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Constants for user roles
     */
    public const ROLE_ADMIN = 'admin';

    public const ROLE_DEPARTMENT_HEAD = 'department_head';

    public const ROLE_PROGRAM_HEAD = 'program_head';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_STUDENT = 'student';

    /**
     * Constants for user status
     */
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * Get all available roles
     */
    public static function getAllRoles(): array
    {
        return [
            self::ROLE_ADMIN,
            self::ROLE_DEPARTMENT_HEAD,
            self::ROLE_PROGRAM_HEAD,
            self::ROLE_INSTRUCTOR,
            self::ROLE_STUDENT,
        ];
    }

    /**
     * Scope: Get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Get only users with active accounts (is_active === true).
     * SECURITY: Useful for queries that need to exclude deactivated users.
     */
    public function scopeAccountActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user account is active.
     * SECURITY: Used by middleware to enforce access control.
     */
    public function isAccountActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Deactivate user account.
     * SECURITY: This method immediately blocks user access via middleware.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Reactivate user account.
     *
     * @return bool
     */
    public function reactivate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Send password reset notification
     *
     * SECURITY: This method is called by Laravel's password broker
     * when a user requests a password reset.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Get role label for display
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_DEPARTMENT_HEAD => 'Department Head',
            self::ROLE_PROGRAM_HEAD => 'Program Head',
            self::ROLE_INSTRUCTOR => 'Instructor',
            self::ROLE_STUDENT => 'Student',
            default => ucfirst($this->role),
        };
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * ========================================
     * FACULTY LOAD MANAGEMENT RELATIONSHIPS
     * ========================================
     */

    /**
     * Get all subjects this user (instructor) can teach.
     * Returns a many-to-many relationship through faculty_subjects pivot table.
     */
    public function facultySubjects()
    {
        return $this->belongsToMany(Subject::class, 'faculty_subjects')
                    ->withPivot('lecture_hours', 'lab_hours', 'computed_units', 'max_load_units')
                    ->withTimestamps();
    }

    /**
     * Calculate teaching units based on lecture and lab hours.
     *
     * CONVERSION RULES:
     * - Lecture: 1 hour = 1 unit
     * - Laboratory: 3 hours = 1 unit
     *
     * @param int $lectureHours
     * @param int $labHours
     * @return float
     */
    public static function calculateTeachingUnits(int $lectureHours = 0, int $labHours = 0): float
    {
        $lectureUnits = $lectureHours * 1;
        $labUnits = $labHours / 3;

        return round($lectureUnits + $labUnits, 2);
    }

    /**
     * Get aggregated teaching load summary for this instructor.
     * Returns total lecture hours, lab hours, and teaching units across all assignments.
     *
     * @return array
     */
    public function getTeachingLoadSummary(): array
    {
        $assignments = $this->facultySubjects()->get();

        $totalLectureHours = $assignments->sum('pivot.lecture_hours');
        $totalLabHours = $assignments->sum('pivot.lab_hours');
        $totalUnits = $assignments->sum('pivot.computed_units');

        return [
            'total_lecture_hours' => $totalLectureHours,
            'total_lab_hours' => $totalLabHours,
            'total_teaching_units' => round($totalUnits, 2),
            'assignment_count' => $assignments->count(),
        ];
    }

    /**
     * Check if this user is an eligible instructor.
     * Eligible roles: instructor, program_head, department_head
     */
    public function isEligibleInstructor(): bool
    {
        return in_array($this->role, [
            self::ROLE_INSTRUCTOR,
            self::ROLE_PROGRAM_HEAD,
            self::ROLE_DEPARTMENT_HEAD,
        ]);
    }

    /**
     * Get all eligible instructors (can teach subjects).
     * Scope for querying eligible instructors from the database.
     */
    public function scopeEligibleInstructors($query)
    {
        return $query->whereIn('role', [
            self::ROLE_INSTRUCTOR,
            self::ROLE_PROGRAM_HEAD,
            self::ROLE_DEPARTMENT_HEAD,
        ]);
    }

    /**
     * Get subjects with faculty load constraints for this instructor.
     * Useful for Schedule Generation module later.
     */
    public function getTeachableSubjectsWithConstraints()
    {
        return $this->facultySubjects()
                    ->with('program')
                    ->get();
    }
}
