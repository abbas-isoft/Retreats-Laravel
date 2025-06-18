<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailabilityRecord extends Model
{
    protected $fillable = [
        'property_id',
        'start_date',
        'end_date',
        'availability',
        'change_over',
        'max_stay',
        'min_stay',
        'min_prior_notify',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];
}
