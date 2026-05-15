<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    protected $fillable = ['title', 'subtitle', 'image', 'tagline', 'description', 'button_text', 'active', 'video_url'];

    public function getImageAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }

}
