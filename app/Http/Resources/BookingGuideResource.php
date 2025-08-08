<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingGuideResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            // 'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'video_url' => $this->video_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
