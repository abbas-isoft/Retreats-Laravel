<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyContentImage extends Model
{
    protected $fillable = ['property_content_id', 'photo_id', 'url'];

    public function content()
    {
        return $this->belongsTo(PropertyContent::class, 'property_content_id');
    }
}
