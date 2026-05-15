<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingGuide extends Model
{
    protected $fillable = ['title', 'subtitle', 'video_url' , 'image' , 'enquire_now_button' , 'description' , 'is_video'];

    public function getImageAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }
}
