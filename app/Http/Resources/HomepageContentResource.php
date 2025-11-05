<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomepageContentResource extends JsonResource
{
    public function toArray($request)
    {
        // Convert background_image JSON string to array if needed
        $images = is_string($this->background_image)
            ? json_decode($this->background_image, true)
            : $this->background_image;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'background_image' => $images ?? [],
            'background_video' => $this->background_video,
            'is_video' => $this->is_video,

            // ✅ SEO fields
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,

            // ✅ Open Graph fields
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'og_image' => $this->og_image,

            // ✅ Canonical, Robots, and Schema
            'canonical_url' => $this->canonical_url,
            'schema_markup' => $this->schema_markup,
            'robots_txt' => $this->robots_txt,

            // ✅ Status toggle
            'is_active' => $this->is_active,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
