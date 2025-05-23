<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SingleConfiguration extends Model
{
    protected $fillable = [
        'property_id',
        'property_url',
        'customer_property_id',
        'card_payment_forms',
        'cancellation_policy',
        'check_in_time',
        'check_out_time',
        'children_allowed',
        'max_adults',
        'max_guests',
        'max_children',
        'merchant_name',
        'card_code',
        'card_type',
        'pets_allowed',
        'smoking_allowed',
        'allow_bookings',
        'allow_enquiries',
    ];

    protected $casts = [
        'card_payment_forms' => 'array',
        'children_allowed' => 'boolean',
        'pets_allowed' => 'boolean',
        'smoking_allowed' => 'boolean',
        'allow_bookings' => 'boolean',
        'allow_enquiries' => 'boolean',
    ];
}
