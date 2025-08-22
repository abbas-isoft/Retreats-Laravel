<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'account_id',
        'listing_url',
        'last_updated',
    ];
     public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    public function availabilityCalendars()
    {
        // Define the relationship with the AvailabilityCalendar model
        return $this->belongsTo(AvailabilityCalendar::class, 'property_id');
    }
}
