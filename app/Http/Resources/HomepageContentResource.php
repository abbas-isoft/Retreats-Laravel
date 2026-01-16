<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomepageContentResource extends JsonResource
{
    public function toArray($request)
    {
        $images = is_string($this->background_image)
            ? json_decode($this->background_image, true)
            : $this->background_image;

        return [
        'id' => $this->id,
        'title' => $this->title,
        'subtitle' => $this->subtitle,
        'background_image' => json_decode($this->background_image ?? '[]', true),
        'background_video' => $this->background_video,
        'is_video' => $this->is_video,
        'seo' => [
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og' => [
                'title' => $this->og_title,
                'description' => $this->og_description,
                'image' => $this->og_image,
                'url' => $this->og_url,
                'type' => $this->og_type,
                'site_name' => $this->og_site_name,
                'width' => $this->og_image_width,
                'height' => $this->og_image_height,
            ],
            'twitter' => [
                'card' => $this->twitter_card,
                'title' => $this->twitter_title,
                'description' => $this->twitter_description,
                'image' => $this->twitter_image,
                'site' => $this->twitter_site,
            ],
            'canonical_url' => $this->canonical_url,
            'schema_markup' => $this->schema_markup,
            'robots_txt' => $this->robots_txt,
            'is_active' => $this->is_active,
        ],
    ];
    }
}
