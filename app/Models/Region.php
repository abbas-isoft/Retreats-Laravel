<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';
    protected $fillable = ['name', 'description', 'image'];
    public $timestamps = true;

    public function properties()
{
    return $this->belongsToMany(PropertyContent::class, 'region_property', 'region_id', 'property_id');
}

}
