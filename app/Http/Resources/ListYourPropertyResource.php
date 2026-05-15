<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListYourPropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
          'image' => $this->image && !str_starts_with($this->image, 'http') ? env('ADMIN_URL') . '/' . ltrim($this->image, '/') : $this->image,
            'button_text' => $this->button_text,
            'button_link' => $this->button_link,
        ];
    }
}

