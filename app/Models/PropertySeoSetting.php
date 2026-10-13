<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertySeoSetting extends Model
{
    public const TEXT_FIELDS = [
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'og_url', 'og_type', 'og_site_name',
        'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image', 'twitter_site',
        'schema_markup',
    ];

    protected $guarded = ['id'];

    protected $attributes = ['is_active' => true, 'robots_index' => true];

    protected $casts = [
        'is_active' => 'boolean', 'robots_index' => 'boolean',
        'og_image_width' => 'integer', 'og_image_height' => 'integer',
    ];

    public function toSeoArray(): array
    {
        $data = [];
        foreach (self::TEXT_FIELDS as $field) {
            $data[$field] = $this->getAttribute($field);
        }
        foreach (['og_image_width', 'og_image_height', 'robots_index', 'is_active'] as $field) {
            $data[$field] = $this->getAttribute($field);
        }
        return $data;
    }
}
