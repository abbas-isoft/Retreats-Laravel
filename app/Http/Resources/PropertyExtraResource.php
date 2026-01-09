<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyExtraResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->extra_id,
            'property_id' => $this->property_id,
            'category'   => $this->category,
            'name'       => $this->name,
            'description' => $this->description,
            'img'        => $this->img,
            'price' => [
                'rate'        => $this->price_rate,
                'unit'        => $this->price_unit,
                'unit_label'  => $this->price_unit_label,
                'total'       => $this->price_total,
                'currency_symbol' => $this->currency_symbol,
            ],
            'available'   => $this->available,
            'qty'         => $this->qty,
            'is_default'  => $this->is_default,
            'is_hidden'   => $this->is_hidden,
            'is_included' => $this->is_included,
        ];
    }
}
