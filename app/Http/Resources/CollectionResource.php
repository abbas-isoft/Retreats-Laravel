<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CollectionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image && !str_starts_with($this->image, 'http') ? env('ADMIN_URL') . '/' . $this->image : $this->image,
        ];
    }
}
