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
        'amenities' // This will be stored as JSON
    ];

     protected $casts = [
        'amenities' => 'array',
    ];

    public function images()
    {
        return $this->hasMany(PropertyContentImage::class);
    }
    public function rooms() {
    return $this->hasMany(PropertyRoom::class);
}
}
