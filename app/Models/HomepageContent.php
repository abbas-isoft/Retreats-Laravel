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
    ];

    protected $casts = [
        'background_image' => 'array',
        'is_video'         => 'boolean',
        'robots_txt'       => 'boolean',
        'is_active'        => 'boolean',
        'schema_markup'    => 'array',
    ];

    private function formatAdminUrl($value)
    {
        return $value && !str_starts_with($value, 'http') ? env('ADMIN_URL') . '/' . $value : $value;
    }

    public function getBackgroundImageAttribute($value)
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (is_array($decoded)) {
            return array_map([$this, 'formatAdminUrl'], $decoded);
        }
        return $this->formatAdminUrl($value);
    }

    public function getBackgroundVideoAttribute($value)
    {
        return $this->formatAdminUrl($value);
    }

    public function getOgImageAttribute($value)
    {
        return $this->formatAdminUrl($value);
    }

    public function getTwitterImageAttribute($value)
    {
        return $this->formatAdminUrl($value);
    }
}
