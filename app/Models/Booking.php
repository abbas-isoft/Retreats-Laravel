<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
    'property_id',
    'start_date',
    'nights',
    'guest_firstname',
    'guest_lastname',
    'email',
    'phone',
    'value',
    'booking_reference',
    'supercontrol_response',
];
}
