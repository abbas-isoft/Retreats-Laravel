<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListPropertyResource extends JsonResource
{
    public function toArray($request)
    {
        $image = $this->hero_image;
        if ($image && !str_starts_with($image, 'http')) {
            $image = url($image);
        }

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'subtitle'    => $this->subtitle,
            'description' => $this->description,
            'hero_image'  =>url('images/' . $this->hero_image),
            'button_text' => $this->button_text,
        ];
    }
}
