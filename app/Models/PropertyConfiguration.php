<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyConfiguration extends Model
{
    protected $fillable = [
        'property_id',
        'account_id',
        'card_code',
        'card_type',
        'cancellation_policy',
        'merchant_name',
        'minimum_age',
        'rental_agreement_url',
        'last_updated',
        'configurationContentUrl',
    ];
}
