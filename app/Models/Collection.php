<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['title', 'description', 'image'];

    public function getImageAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }

    public function properties()
{
    return $this->belongsToMany(
        \App\Models\PropertyContent::class,
        'collection_property',
        'collection_id',
        'property_id'
    );
}
}
