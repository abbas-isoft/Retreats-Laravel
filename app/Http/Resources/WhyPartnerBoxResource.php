<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WhyPartnerBoxResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'section_title'  => $this->section_title,
            'box_title'      => $this->box_title,
            'box_description'=> $this->box_description,
        ];
    }
}
