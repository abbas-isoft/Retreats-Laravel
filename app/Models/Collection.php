<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['title', 'description', 'image'];

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
