<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyCalendar extends Model
{
    protected $fillable = [
        'listing_id',
        'calendar_day_id',
        'date',
        'is_available',
        'is_processed',
        'status',
        'price',
        'minimum_stay',
        'maximum_stay',
        'closed_on_arrival',
        'closed_on_departure',
        'note',
        'count_available_units',
        'available_units_to_sell',
        'count_pending_units',
        'count_blocking_reservations',
        'count_blocked_units',
        'count_reserved_units',
        'desired_units_to_sell',
        'reservations',
    ];

    protected $casts = [
        'reservations' => 'array',
        'date' => 'date',
    ];
}
