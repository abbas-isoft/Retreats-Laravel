<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestBooking extends Model
{
    use HasFactory;

    protected $table = 'test_bookings';

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
        'booking_id',
        'supercontrol_response',

        // Address & Guest Details
        'address1',
        'town',
        'postcode',
        'country',
        'adults',
        'children',
        'infants',
        'special_requests',

        // Booking Extras
        'dog_selected',
        'dog_count',

        // Add-ons
        'hamper',
        'firewood',
        'decoration',

        // Terms
        'accepted_terms',
        'accepted_rules',
    ];
}
