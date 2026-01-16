<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['department_code', 'department_name'];

    /**
     * Get the programs that belong to this department.
     */
    public function programs()
    {
        return $this->hasMany(Program::class);
    }
}
