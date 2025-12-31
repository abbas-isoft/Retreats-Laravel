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

    //  Payment & Stripe Fields
    'stripe_customer_id',
    'stripe_payment_method_id',
    'upfront_payment_intent_id',
    'remaining_payment_intent_id',
    'damage_deposit_intent_id',
    'stripe_transaction_id',
    'receipt_url',

    'amount_cents',
    'upfront_cents',
    'remaining_cents',
    'damage_deposit_cents',

    'remaining_payment_scheduled_at',
    'deposit_hold_scheduled_at',

    'payment_status',
];

}
