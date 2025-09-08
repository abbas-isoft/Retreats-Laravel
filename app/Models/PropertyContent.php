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
    public function propertyContents()
{
    return $this->hasMany(PropertyContent::class);
}

    public function filteredImages($filter)
{
    return $this->hasMany(PropertyContentImage::class)
                ->where('tag', $filter); // assuming 'tag' stores things like 'beach_stays'
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

    public function listing()
{
    return $this->hasOne(ContentListing::class, 'property_id', 'property_id');
}

public function availabilityRecords() 
{
    return $this->hasMany(AvailabilityRecord::class, 'property_id');
}

public function configuration()
{
    return $this->hasOne(SingleConfiguration::class, 'property_id', 'id');
}


    public function rooms()
    {
        return $this->hasMany(PropertyRoom::class);
    }
    public function beds()
    {
        return $this->hasManyThrough(PropertyRoomBed::class, PropertyRoom::class);
    }

   public function amenities()
{
    return $this->belongsToMany(Amenity::class, 'amenity_property', 'property_id', 'amenity_id');
}

public function propertyAvailability()
{
    return $this->hasOne(PropertyAvailability::class, 'property_id', 'property_id');
}
public function AvailabilityCalendar()
    {
        return $this->hasMany(AvailabilityCalendar::class, 'property_id');
    }

     public function reviews()
    {
        return $this->hasMany(Review::class, 'property_id', 'property_id');
    }

    public function content_listings()
{
    return $this->hasMany(ContentListing::class, 'property_id', 'property_id');
}
}
