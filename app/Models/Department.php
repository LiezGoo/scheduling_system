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

    /**
     * Get the department head assigned to this department.
     * A department can have one department head.
     */
    public function departmentHead()
    {
        return $this->hasOne(User::class, 'department_id')
                    ->where('role', User::ROLE_DEPARTMENT_HEAD);
    }

    /**
     * Check if this department has an assigned department head.
     */
    public function hasDepartmentHead(): bool
    {
        return $this->departmentHead()->exists();
    }
}
