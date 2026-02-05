<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'room_name',
        'room_type',
    ];

    /**
     * Scope to filter rooms by room type (case-insensitive).
     */
    public function scopeWhereRoomType($query, $roomType)
    {
        return $query->whereRaw('LOWER(room_type) = ?', [strtolower($roomType)]);
    }
}
