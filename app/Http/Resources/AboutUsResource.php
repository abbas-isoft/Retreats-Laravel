<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'tagline' => $this->tagline,
        'subtitle' => $this->subtitle,
        'image' => $this->image && !str_starts_with($this->image, 'http') ? env('ADMIN_URL') . '/' . $this->image : $this->image,
        'description' => $this->description,
        'button_text' => $this->button_text,
    ];
}
}
