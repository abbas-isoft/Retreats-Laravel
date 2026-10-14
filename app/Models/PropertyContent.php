<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// use App\Models\PropertyContentImage;
class PropertyContent extends Model
{
    protected $fillable = [
        'property_id',
        'hostaway_id',
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
        'admin_amenities',
        'property_page_amenities',
    ];

    protected $casts = [
        'amenities' => 'array',
        'property_page_amenities' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(\App\Models\PropertyContentImage::class, 'property_content_id', 'id')->orderBy('sort_order', 'asc');
    }

    public function seoSettings()
    {
        return $this->hasOne(PropertySeoSetting::class, 'property_id', 'property_id');
    }

    public function propertyPageAmenityDetails(): ?array
    {
        $ids = $this->property_page_amenities;
        if ($ids === null) {
            return null;
        }
        if (!$ids) {
            return [];
        }

        $amenities = Amenity::select('id', 'name')->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(fn ($id) => $amenities->get($id))
            ->filter()->map(fn ($amenity) => ['id' => $amenity->id, 'name' => $amenity->name])
            ->values()->all();
    }
    public function propertyContents()
    {
        return $this->hasMany(PropertyContent::class);
    }

    public function filteredImages($filter)
    {
        return $this->hasMany(PropertyContentImage::class)
            ->where('tag', $filter);
    }



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

    public function regions()
    {
        return $this->belongsToMany(
            \App\Models\Region::class,
            'region_property',
            'property_id',
            'region_id'
        );
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
    public function collections()
    {
        return $this->belongsToMany(
            \App\Models\Collection::class,
            'collection_property',
            'property_id',
            'collection_id'
        );
    }
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
