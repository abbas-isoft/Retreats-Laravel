<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OwnerSaySectionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'owner_name'          => $this->owner_name,
            'secondary_title'     => $this->secondary_title,
            'secondary_description'=> $this->secondary_description,
            'image'               => $this->image ? asset('images/'.$this->image) : null,
        ];
    }
}
