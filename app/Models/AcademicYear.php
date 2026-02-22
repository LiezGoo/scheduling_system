<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcademicYear extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_year',
        'end_year',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model.
     * Automatically set name based on start_year and end_year.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($academicYear) {
            if (empty($academicYear->name)) {
                $academicYear->name = $academicYear->start_year . '-' . $academicYear->end_year;
            }
        });

        static::updating(function ($academicYear) {
            if ($academicYear->isDirty(['start_year', 'end_year'])) {
                $academicYear->name = $academicYear->start_year . '-' . $academicYear->end_year;
            }
        });
    }

    /**
     * Get the semesters for this academic year.
     */
    public function semesters()
    {
        return $this->hasMany(Semester::class);
    }

    /**
     * Activate this academic year and deactivate all others.
     * Also deactivates all semesters that don't belong to this academic year.
     */
    public function activate()
    {
        return DB::transaction(function () {
            // Deactivate all other academic years
            static::where('id', '!=', $this->id)->update(['is_active' => false]);

            // Deactivate all semesters that don't belong to this academic year
            Semester::whereNotIn('academic_year_id', [$this->id])->update(['is_active' => false]);

            // Activate this academic year
            $this->update(['is_active' => true]);

            return true;
        });
    }

    /**
     * Get the currently active academic year.
     */
    public static function getActive()
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Get the active semester for this academic year.
     */
    public function getActiveSemester()
    {
        return $this->semesters()->where('is_active', true)->first();
    }

    /**
     * Scope a query to only include active academic years.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
