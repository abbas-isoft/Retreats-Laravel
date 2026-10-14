<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedArrival extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function properties()
    {
        return $this->belongsToMany(
            \App\Models\PropertyContent::class,
            'featured_arrival_property',
            'featured_arrival_id',
            'property_id',
            'id',        
            'property_id'   
        )->withPivot('sort_order')->orderByPivot('sort_order')->orderByPivot('id');
    }
}
