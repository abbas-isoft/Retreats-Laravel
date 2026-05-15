<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'button_text',
        'background_video_url',
    ];

    public function getBackgroundVideoUrlAttribute($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }
}

