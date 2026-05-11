<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyContentImage extends Model
{
    protected $table = 'property_content_images';
    protected $fillable = ['property_content_id', 'photo_id', 'url', 'sort_order'];

    public static function content($id)
    {
        return self::where('property_content_id', $id)->get();
    }
      public function propertyContent()
    {
        return $this->belongsTo(PropertyContent::class);
    }
}
