<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Program extends Model
{
    protected $fillable = [
        'program_code',
        'program_name',
        'department_id',
    ];

    /**
     * Get the department that this program belongs to.
     */
    public function departments(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Subjects offered in this program's curriculum.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'program_subjects')
            ->withPivot(['year_level', 'semester'])
            ->withTimestamps();
    }

    /**
     * Get the program head assigned to this program.
     * A program can have one program head.
     */
    public function programHead()
    {
        return $this->hasOne(User::class, 'program_id')
                    ->where('role', User::ROLE_PROGRAM_HEAD);
    }

    /**
     * Check if this program has an assigned program head.
     */
    public function hasProgramHead(): bool
    {
        return $this->programHead()->exists();
    }
}
