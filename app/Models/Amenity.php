<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    protected $fillable = ['name'];

      public function properties()
    {
        return $this->belongsToMany(PropertyContent::class, 'amenity_property', 'amenity_id', 'property_id');

    }
    public function propertyContents()
{
    return $this->belongsToMany(PropertyContent::class, 'property_content_amenity');
}
}
