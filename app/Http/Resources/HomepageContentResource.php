<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class HomepageContentResource extends JsonResource
{
    public function toArray($request)
    {
        //  background_image ko hamesha array bana do
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
