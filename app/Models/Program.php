<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
