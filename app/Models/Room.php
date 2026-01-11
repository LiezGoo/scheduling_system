<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'room_name',
        'building_id',
        'room_type_id',
    ];

    /**
     * Get the building that contains this room.
     */
    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Get the room type.
     */
    public function roomType()
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }
}
