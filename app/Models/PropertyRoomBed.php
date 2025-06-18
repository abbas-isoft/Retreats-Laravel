<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRoomBed extends Model
{
    protected $fillable = [
        'property_room_id',
        'bed_type',
        'quantity',
    ];

    public function room()
    {
        return $this->belongsTo(PropertyRoom::class, 'property_room_id');
    }
}
