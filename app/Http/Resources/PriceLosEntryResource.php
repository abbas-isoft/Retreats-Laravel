<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceLosEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date,
            'length_of_stay_prices' => $this->length_of_stay_prices,
        ];
    }
}
