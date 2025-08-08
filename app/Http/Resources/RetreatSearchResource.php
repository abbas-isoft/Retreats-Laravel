<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetreatSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'city'       => $this->city,
            'check_in'   => $this->check_in,
            'check_out'  => $this->check_out,
            'guests'     => $this->guests,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
