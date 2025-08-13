<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookingGuideResource extends JsonResource
{
    public function toArray($request)
    {
        return [
        //    'title' => $this->title,
        'subtitle' => $this->subtitle,
        'video_url' => $this->video_url,
        'image' => url('images/' . $this->image),
        'enquire_now_button' => $this->enquire_now_button,  // And this line
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
        ];
    }
}
