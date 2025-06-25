<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceFeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'vat' => $this->vat,
            'refundable' => $this->refundable,
            'hidden' => $this->hidden,
            'fee_payable_with' => $this->fee_payable_with,
        ];
    }
}
