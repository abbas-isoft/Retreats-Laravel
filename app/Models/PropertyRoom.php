<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyRoom extends Model
{
    protected $fillable = [
        'property_content_id',
        'name'
    ];

    public function beds()
    {
        return $this->hasMany(PropertyRoomBed::class);
    }

    public function content()
    {
        return $this->belongsTo(PropertyContent::class, 'property_content_id');
    }
}
