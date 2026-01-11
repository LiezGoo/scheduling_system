<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $fillable = [
        'type_name',
        'description',
    ];

    /**
     * Get the rooms of this type.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
