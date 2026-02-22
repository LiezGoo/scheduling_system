<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year_id',
        'name',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'academic_year_id' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Valid semester names
     */
    public const VALID_NAMES = [
        '1st Semester',
        '2nd Semester',
        'Summer',
    ];

    /**
     * Get the academic year that this semester belongs to.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Activate this semester and deactivate all other semesters in the same academic year.
     * Also activates the parent academic year.
     */
    public function activate()
    {
        return DB::transaction(function () {
            // Deactivate all semesters in the same academic year
            static::where('academic_year_id', $this->academic_year_id)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);

            // Activate this semester
            $this->update(['is_active' => true]);

            // Ensure the academic year is active
            if ($this->academicYear && !$this->academicYear->is_active) {
                $this->academicYear->activate();
            }

            return true;
        });
    }

    /**
     * Get the currently active semester across all academic years.
     */
    public static function getActive()
    {
        return static::where('is_active', true)->with('academicYear')->first();
    }

    /**
     * Scope a query to only include active semesters.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this semester name is valid.
     */
    public static function isValidName(string $name): bool
    {
        return in_array($name, self::VALID_NAMES);
    }
}
