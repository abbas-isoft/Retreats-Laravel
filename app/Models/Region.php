<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regions';
    protected $fillable = ['name', 'description', 'image' , 'active'];
    public $timestamps = true;

    public function getImageAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }

    public function properties()
{
    return $this->belongsToMany(PropertyContent::class, 'region_property', 'region_id', 'property_id');
}

}
