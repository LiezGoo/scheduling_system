<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Building;

class Room extends Model
{
    protected $fillable = [
        'room_code',
        'room_name',
        'room_type',
        'building_id',
    ];

    /**
     * Get the building that owns this room.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Scope to filter rooms by room type (case-insensitive).
     */
    public function scopeWhereRoomType($query, $roomType)
    {
        return $query->whereRaw('LOWER(room_type) = ?', [strtolower($roomType)]);
    }
}
