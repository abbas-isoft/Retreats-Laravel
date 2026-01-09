<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageContent extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'background_image',
        'background_video',
        'is_video',


        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'canonical_url',
        'schema_markup',
        'robots_txt',
        'is_active',
    ];

    protected $casts = [
        'background_image' => 'array',
        'is_video'         => 'boolean',
        'robots_txt'       => 'boolean',
        'is_active'        => 'boolean',
        'schema_markup'    => 'array',
    ];
}
