<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUs extends Model
{
    protected $fillable = ['title', 'subtitle', 'image', 'tagline', 'description', 'button_text', 'active', 'video_url'];

}
