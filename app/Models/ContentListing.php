<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentListing extends Model
{
    protected $fillable = [
        'property_id',
        'property_url',
        'customer_property_id',
        'accepted_payment_forms',
        'cancellation_policy',
        'check_in_time',
        'check_out_time',
        'children_allowed',
        'maximum_adults',
        'maximum_guests',
        'maximum_children',
        'merchant_name',
        'pets_allowed',
        'smoking_allowed',
        'allow_bookings',
        'allow_enquiries',
    ];

    protected $casts = [
        'accepted_payment_forms' => 'array',
        'children_allowed' => 'boolean',
        'pets_allowed' => 'boolean',
        'smoking_allowed' => 'boolean',
        'allow_bookings' => 'boolean',
        'allow_enquiries' => 'boolean',
    ];
}
