<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Program extends Model
{
    protected $fillable = [
        'program_code',
        'program_name',
        'id',
    ];

    public function departments() {
        return $this->belongsTo(Department::class);
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
}
