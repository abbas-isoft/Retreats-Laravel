<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'review'         => $this->review,
            'reviewer_name'  => $this->reviewer_name,
            'location'       => $this->location,
            'arrival_date'   => $this->arrival_date ?? null,
            'departure_date' => $this->departure_date ?? null,
        ];
    }
}
