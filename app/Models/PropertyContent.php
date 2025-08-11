<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// use App\Models\PropertyContentImage;
class PropertyContent extends Model
{
    protected $fillable = [
        'property_id',
        'customer_property_id',
        'active',
        'property_name',
        'subcaption',
        'description',
        'accommodations_summary',
        'location_description',
        'changeover',
        'second',
        'address_line',
        'city',
        'country',
        'post_code',
        'description',
        'latitude',
        'longitude',
        'property_type',
        'registration_number',
        'epcRating',
        'bedroom_count',
        'bathroom_count',
        'area',
        'area_unit',
        'amenities',
    ];

    protected $casts = [
        'amenities' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(PropertyContentImage::class);
    }


    // optional: helper to check amenity key in PHP
    public function hasAmenityKey($key)
    {
        if (is_array($this->amenities)) {
            return in_array($key, $this->amenities);
        }
        if (is_string($this->amenities)) {
            return stripos($this->amenities, $key) !== false;
        }
        return false;
    }


    public function rooms()
    {
        return $this->hasMany(PropertyRoom::class);
    }
    public function beds()
    {
        return $this->hasManyThrough(PropertyRoomBed::class, PropertyRoom::class);
    }
}
