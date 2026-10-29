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

    protected $fillable = [
        'property_id', 'collection_id', 'region_id',
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        'og_title', 'og_description', 'og_image', 'og_url', 'og_type', 'og_site_name',
        'og_image_width', 'og_image_height', 'twitter_card', 'twitter_title',
        'twitter_description', 'twitter_image', 'twitter_site', 'robots_index',
        'schema_markup', 'is_active',
    ];

    protected $attributes = [
        'og_type' => 'website',
        'og_site_name' => 'Group Retreats',
        'og_image_width' => 1200,
        'og_image_height' => 630,
        'twitter_card' => 'summary_large_image',
        'robots_index' => true,
        'is_active' => true,
    ];

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
        if (is_string($data['schema_markup'])) {
            $data['schema_markup'] = json_decode($data['schema_markup']);
        }
        foreach (['og_image_width', 'og_image_height', 'robots_index', 'is_active'] as $field) {
            $data[$field] = $this->getAttribute($field);
        }

        return $data;
    }
}
