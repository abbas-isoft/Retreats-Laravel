<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HeroSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'button_text' => $this->button_text,
            'background_video_url' => $this->background_video_url && !str_starts_with($this->background_video_url, 'http') ? env('ADMIN_URL') . '/' . $this->background_video_url : $this->background_video_url,
        ];
    }
}

