<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'cta_text'    => $this->cta_text,
            'image_url'   => $this->image_url
                ? (str_starts_with($this->image_url, 'http')
                    ? $this->image_url
                    : asset('storage/images/' . $this->image_url))
                : null,
            'video_url'   => $this->video_url,
            'description' => $this->description,
            'note'        => $this->note,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
