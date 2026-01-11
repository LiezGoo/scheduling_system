<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $fillable = [
        'building_code',
        'building_name',
        'description',
    ];

    /**
     * Get the rooms in this building.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
