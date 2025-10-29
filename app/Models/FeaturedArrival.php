<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedArrival extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    // properties in property_contents use `property_id` (string)
    public function properties()
    {
        return $this->belongsToMany(
            \App\Models\PropertyContent::class,
            'featured_arrival_property',
            'featured_arrival_id',
            'property_id',
            'id',           // local key on PropertyContent (id)
            'property_id'   // related key on PropertyContent (property_id)
        );
    }
}
