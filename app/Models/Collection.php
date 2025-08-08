<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['title', 'description', 'image'];

    public function properties()
    {
        return $this->belongsToMany(Property::class, 'collection_property');
    }
}

