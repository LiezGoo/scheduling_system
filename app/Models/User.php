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
        'department_id',
        'program_id',
        'faculty_scheme',
        'employment_type',
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
     *
     * CRITICAL: There is NO standalone "Administrator" role.
     * department_head IS the administrator, but ONLY within their assigned department.
     */
    public const ROLE_DEPARTMENT_HEAD = 'department_head';

    public const ROLE_PROGRAM_HEAD = 'program_head';

    public const ROLE_INSTRUCTOR = 'instructor';

    public const ROLE_STUDENT = 'student';

    /**
     * Fixed faculty scheme options for teaching roles
     */
    public const FACULTY_SCHEMES = [
        '7:00 AM – 4:00 PM',
        '8:00 AM – 5:00 PM',
        '10:00 AM – 7:00 PM',
    ];

    /**
     * Employment types for faculty load constraints
     */
    public const EMPLOYMENT_PERMANENT = 'permanent';
    public const EMPLOYMENT_CONTRACT_27 = 'contract_27';
    public const EMPLOYMENT_CONTRACT_24 = 'contract_24';

    /**
     * Faculty load limits (hours per week)
     *
     * CONTRACT FACULTY:
     * - contract_27: Maximum 27 total hours per week
     * - contract_24: Maximum 24 total hours per week
     *
     * PERMANENT FACULTY:
     * - Maximum 21 lab hours per week
     * - Maximum 18 lecture hours per week
     */
    public const MAX_HOURS_CONTRACT_27 = 27;
    public const MAX_HOURS_CONTRACT_24 = 24;
    public const MAX_LAB_HOURS_PERMANENT = 21;
    public const MAX_LECTURE_HOURS_PERMANENT = 18;

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
            self::ROLE_DEPARTMENT_HEAD,
            self::ROLE_PROGRAM_HEAD,
            self::ROLE_INSTRUCTOR,
            self::ROLE_STUDENT,
        ];
    }

    /**
     * Get all fixed faculty scheme options.
     */
    public static function getFacultySchemes(): array
    {
        return self::FACULTY_SCHEMES;
    }

    /**
     * Roles that require a faculty scheme.
     */
    public static function rolesRequiringScheme(): array
    {
        return [
            self::ROLE_DEPARTMENT_HEAD,
            self::ROLE_PROGRAM_HEAD,
            self::ROLE_INSTRUCTOR,
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
     *
     * SECURITY: There is no standalone admin role.
     * department_head IS the admin within their department.
     */
    public function isAdmin(): bool
    {
        return false; // Explicitly disabled
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
     * Get maximum allowed hours based on employment type.
     * Returns array with max_lecture_hours, max_lab_hours, and max_total_hours.
     *
     * @return array
     */
    public function getMaxAllowedHours(): array
    {
        switch ($this->employment_type) {
            case self::EMPLOYMENT_CONTRACT_27:
                return [
                    'max_lecture_hours' => self::MAX_HOURS_CONTRACT_27,
                    'max_lab_hours' => self::MAX_HOURS_CONTRACT_27,
                    'max_total_hours' => self::MAX_HOURS_CONTRACT_27,
                    'type' => 'contract_27',
                ];

            case self::EMPLOYMENT_CONTRACT_24:
                return [
                    'max_lecture_hours' => self::MAX_HOURS_CONTRACT_24,
                    'max_lab_hours' => self::MAX_HOURS_CONTRACT_24,
                    'max_total_hours' => self::MAX_HOURS_CONTRACT_24,
                    'type' => 'contract_24',
                ];

            case self::EMPLOYMENT_PERMANENT:
                return [
                    'max_lecture_hours' => self::MAX_LECTURE_HOURS_PERMANENT,
                    'max_lab_hours' => self::MAX_LAB_HOURS_PERMANENT,
                    'max_total_hours' => null, // No total limit, only separate limits
                    'type' => 'permanent',
                ];

            default:
                // No employment type set - return null limits (no restrictions)
                return [
                    'max_lecture_hours' => null,
                    'max_lab_hours' => null,
                    'max_total_hours' => null,
                    'type' => 'unspecified',
                ];
        }
    }

    /**
     * Check if adding additional hours would exceed faculty load limits.
     * Returns validation result with detailed message.
     *
     * @param int $additionalLectureHours Hours to add
     * @param int $additionalLabHours Hours to add
     * @return array ['valid' => bool, 'message' => string, 'current' => array, 'limits' => array]
     */
    public function validateFacultyLoad(int $additionalLectureHours = 0, int $additionalLabHours = 0): array
    {
        // No validation needed for non-instructors
        if (!$this->isEligibleInstructor()) {
            return [
                'valid' => true,
                'message' => 'User is not an eligible instructor.',
            ];
        }

        // Get current load
        $current = $this->getTeachingLoadSummary();
        $currentLectureHours = $current['total_lecture_hours'];
        $currentLabHours = $current['total_lab_hours'];
        $currentTotalHours = $currentLectureHours + $currentLabHours;

        // Get limits
        $limits = $this->getMaxAllowedHours();

        // Calculate new totals
        $newLectureHours = $currentLectureHours + $additionalLectureHours;
        $newLabHours = $currentLabHours + $additionalLabHours;
        $newTotalHours = $newLectureHours + $newLabHours;

        // Validate based on employment type
        switch ($this->employment_type) {
            case self::EMPLOYMENT_CONTRACT_27:
            case self::EMPLOYMENT_CONTRACT_24:
                // Contract faculty: Check total hours only
                if ($newTotalHours > $limits['max_total_hours']) {
                    return [
                        'valid' => false,
                        'message' => "Faculty load limit exceeded. {$this->full_name} (Contract {$limits['max_total_hours']}hrs) would have {$newTotalHours} total hours (max: {$limits['max_total_hours']} hours). Current: {$currentTotalHours} hours.",
                        'current' => [
                            'lecture_hours' => $currentLectureHours,
                            'lab_hours' => $currentLabHours,
                            'total_hours' => $currentTotalHours,
                        ],
                        'new' => [
                            'lecture_hours' => $newLectureHours,
                            'lab_hours' => $newLabHours,
                            'total_hours' => $newTotalHours,
                        ],
                        'limits' => $limits,
                    ];
                }
                break;

            case self::EMPLOYMENT_PERMANENT:
                // Permanent faculty: Check lecture and lab hours separately
                if ($newLectureHours > $limits['max_lecture_hours']) {
                    return [
                        'valid' => false,
                        'message' => "Lecture hour limit exceeded. {$this->full_name} (Permanent) would have {$newLectureHours} lecture hours (max: {$limits['max_lecture_hours']} hours). Current: {$currentLectureHours} lecture hours.",
                        'current' => [
                            'lecture_hours' => $currentLectureHours,
                            'lab_hours' => $currentLabHours,
                            'total_hours' => $currentTotalHours,
                        ],
                        'new' => [
                            'lecture_hours' => $newLectureHours,
                            'lab_hours' => $newLabHours,
                            'total_hours' => $newTotalHours,
                        ],
                        'limits' => $limits,
                    ];
                }

                if ($newLabHours > $limits['max_lab_hours']) {
                    return [
                        'valid' => false,
                        'message' => "Lab hour limit exceeded. {$this->full_name} (Permanent) would have {$newLabHours} lab hours (max: {$limits['max_lab_hours']} hours). Current: {$currentLabHours} lab hours.",
                        'current' => [
                            'lecture_hours' => $currentLectureHours,
                            'lab_hours' => $currentLabHours,
                            'total_hours' => $currentTotalHours,
                        ],
                        'new' => [
                            'lecture_hours' => $newLectureHours,
                            'lab_hours' => $newLabHours,
                            'total_hours' => $newTotalHours,
                        ],
                        'limits' => $limits,
                    ];
                }
                break;

            default:
                // No employment type set - allow any load (for now)
                // Future: Require employment_type for all instructors
                break;
        }

        return [
            'valid' => true,
            'message' => 'Faculty load is within limits.',
            'current' => [
                'lecture_hours' => $currentLectureHours,
                'lab_hours' => $currentLabHours,
                'total_hours' => $currentTotalHours,
            ],
            'new' => [
                'lecture_hours' => $newLectureHours,
                'lab_hours' => $newLabHours,
                'total_hours' => $newTotalHours,
            ],
            'limits' => $limits,
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
                    ->with('department')
                    ->get();
    }

    /**
     * ========================================
     * ORGANIZATIONAL SCOPING RELATIONSHIPS
     * ========================================
     */

    /**
     * Get the department this user is head of (for department_head role).
     * Returns null for other roles.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the program this user is head of (for program_head role).
     * Returns null for other roles.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Check if user is a department head.
     */
    public function isDepartmentHead(): bool
    {
        return $this->role === self::ROLE_DEPARTMENT_HEAD;
    }

    /**
     * Check if user is a program head.
     */
    public function isProgramHead(): bool
    {
        return $this->role === self::ROLE_PROGRAM_HEAD;
    }

    /**
     * Check if user has a department assigned.
     * Useful for validating department_head assignments.
     */
    public function hasDepartment(): bool
    {
        return !is_null($this->department_id);
    }

    /**
     * Check if user has a program assigned.
     * Useful for validating program_head assignments.
     */
    public function hasProgram(): bool
    {
        return !is_null($this->program_id);
    }

    /**
     * Get the organizational scope description for display.
     * Examples: "CICT Department", "BSIT Program", "No Assignment"
     */
    public function getOrganizationalScopeAttribute(): string
    {
        if ($this->isDepartmentHead() && $this->department) {
            return $this->department->department_name;
        }

        if ($this->isProgramHead() && $this->program) {
            return $this->program->program_name;
        }

        return 'No Assignment';
    }

    /**
     * Scope: Get department heads with their assigned departments.
     */
    public function scopeDepartmentHeads($query)
    {
        return $query->where('role', self::ROLE_DEPARTMENT_HEAD)
                     ->with('department');
    }

    /**
     * Scope: Get program heads with their assigned programs.
     */
    public function scopeProgramHeads($query)
    {
        return $query->where('role', self::ROLE_PROGRAM_HEAD)
                     ->with('program');
    }

    /**
     * ========================================
     * ROLE-BASED ACCESS CONTROL VALIDATION
     * ========================================
     */

    /**
     * Validate that a department_head has:
     * - department_id set
     * - program_id = NULL
     *
     * @return bool
     */
    public function validateDepartmentHeadRole(): bool
    {
        if ($this->role !== self::ROLE_DEPARTMENT_HEAD) {
            return true; // Only validate department heads
        }

        return !is_null($this->department_id) && is_null($this->program_id);
    }

    /**
     * Validate that a program_head has:
     * - program_id set
     * - program must belong to their inferred department
     *
     * @return bool
     */
    public function validateProgramHeadRole(): bool
    {
        if ($this->role !== self::ROLE_PROGRAM_HEAD) {
            return true; // Only validate program heads
        }

        return !is_null($this->program_id);
    }

    /**
     * Validate that a student has:
     * - program_id set
     *
     * @return bool
     */
    public function validateStudentRole(): bool
    {
        if ($this->role !== self::ROLE_STUDENT) {
            return true; // Only validate students
        }

        return !is_null($this->program_id);
    }

    /**
     * Get the inferred department for this user.
     * For department_head: returns their assigned department
     * For program_head: returns the program's department
     * For instructor/student: returns the program's department (if assigned)
     *
     * @return \App\Models\Department|null
     */
    public function getInferredDepartment(): ?Department
    {
        if (!empty($this->department_id)) {
            return Department::find($this->department_id);
        }

        return $this->program?->department;
    }

    /**
     * Check if user can access a specific department.
     *
     * department_head: Can only access their assigned department
     * Others: Can access if assigned program belongs to that department
     *
     * @param \App\Models\Department|int $department
     * @return bool
     */
    public function canAccessDepartment($department): bool
    {
        $departmentId = $department instanceof Department ? $department->id : $department;

        if ($this->role === self::ROLE_DEPARTMENT_HEAD) {
            return $this->department_id === $departmentId;
        }

        if ($this->program_id) {
            return $this->program?->department_id === $departmentId;
        }

        return false;
    }

    /**
     * Check if user can access a specific program.
     *
     * program_head: Can only access their assigned program
     * department_head: Can access any program in their department
     * Students/Instructors: Can access only their assigned program
     *
     * @param \App\Models\Program|int $program
     * @return bool
     */
    public function canAccessProgram($program): bool
    {
        $programId = $program instanceof Program ? $program->id : $program;

        // Program head: strict single program access
        if ($this->role === self::ROLE_PROGRAM_HEAD) {
            return $this->program_id === $programId;
        }

        // Department head: all programs in their department
        if ($this->role === self::ROLE_DEPARTMENT_HEAD) {
            $program = Program::find($programId);
            return $program && $program->department_id === $this->department_id;
        }

        // Students/Instructors: only their assigned program
        if ($this->program_id) {
            return $this->program_id === $programId;
        }

        return false;
    }

    /**
     * Get all programs accessible to this user.
     * Respects role hierarchy and department/program constraints.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessiblePrograms()
    {
        // Department head: all programs in their department
        if ($this->role === self::ROLE_DEPARTMENT_HEAD) {
            return $this->department->programs()->get();
        }

        // Program head: only their program
        if ($this->role === self::ROLE_PROGRAM_HEAD && $this->program_id) {
            return Program::where('id', $this->program_id)->get();
        }

        // Instructor/Student: only their program
        if ($this->program_id) {
            return Program::where('id', $this->program_id)->get();
        }

        return collect([]);
    }

    /**
     * Scope: Get all users within a specific department scope.
     * Useful for department_head filtering users in their department.
     */
    public function scopeInDepartment($query, $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            // Department head of this department
            $q->where('department_id', $departmentId)
              ->orWhereHas('program', function ($subQ) use ($departmentId) {
                  $subQ->where('department_id', $departmentId);
              });
        });
    }

    /**
     * Scope: Get all users within a specific program scope.
     * Useful for program_head filtering users in their program.
     */
    public function scopeInProgram($query, $programId)
    {
        return $query->where('program_id', $programId);
    }
}
