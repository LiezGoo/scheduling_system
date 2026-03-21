<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = [
        'building_code',
        'building_name',
        'description',
    ];

    /**
     * Get all rooms in this building.
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
