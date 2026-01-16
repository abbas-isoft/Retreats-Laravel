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
            'background_image' => $this->background_image,
            'background_video' => $this->background_video,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,
            'og_url' => $this->og_url,
            'og_type' => $this->og_type,
            'og_site_name' => $this->og_site_name,
            'og_image_width' => $this->og_image_width,
            'og_image_height' => $this->og_image_height,
            'twitter_card' => $this->twitter_card,
            'twitter_title' => $this->twitter_title,
            'twitter_description' => $this->twitter_description,
            'twitter_image' => $this->twitter_image,
            'twitter_site' => $this->twitter_site,
            'canonical_url' => $this->canonical_url,
            'schema_markup' => $this->schema_markup,
            'robots_txt' => $this->robots_txt,
            'is_active' => $this->is_active,
        ];
    }
}
