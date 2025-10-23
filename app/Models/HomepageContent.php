<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'background_image',
        'background_video',   // ✅ added
        'is_active',          // ✅ added
    ];

    // ✅ Optional: auto-cast JSON and boolean fields
    protected $casts = [
        'background_image' => 'array',
        'is_active' => 'boolean',
    ];
}
