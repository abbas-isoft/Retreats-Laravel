<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyRoomBedsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'property_room_id' => $this->property_room_id,
            'bed_type' => $this->bed_type,
            'quantity' => $this->quantity,
        ];
    }
}
