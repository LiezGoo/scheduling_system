<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Semester extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'academic_year_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
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
                ->update(['status' => self::STATUS_INACTIVE]);

            // Activate this semester
            $this->update(['status' => self::STATUS_ACTIVE]);

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
        return static::where('status', self::STATUS_ACTIVE)->with('academicYear')->first();
    }

    /**
     * Scope a query to only include active semesters.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Alias for current table design compatibility.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
