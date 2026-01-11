<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
                    ->withPivot('max_sections', 'max_load_units')
                    ->withTimestamps();
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
