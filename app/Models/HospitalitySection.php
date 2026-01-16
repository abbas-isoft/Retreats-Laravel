<?php

// app/Models/HospitalitySection.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalitySection extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_images',
        'og_url',
        'og_type',
        'og_site_name',
        'og_image_width',
        'og_image_height',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'twitter_site',
        'canonical_url',
        'schema_markup',
        'robots_txt',
        'is_active'
    ];

    protected $casts = [
        'og_images' => 'array',
        'schema_markup' => 'array',
        'robots_txt' => 'boolean',
        'is_active' => 'boolean',
        'og_image_width' => 'integer',
        'og_image_height' => 'integer',
    ];
}
