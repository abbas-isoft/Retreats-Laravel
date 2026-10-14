<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyContentImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'property_content_id' => $this->property_content_id,
            'photo_id' => $this->photo_id,
            'caption' => $this->caption,
            'url' => $this->url && !str_starts_with($this->url, 'http') ? env('APP_URL') . '/' . $this->url : $this->url,
        ];
    }
}
