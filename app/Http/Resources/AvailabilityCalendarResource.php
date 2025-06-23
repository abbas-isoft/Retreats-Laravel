<?php


namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AvailabilityCalendarResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'property_id' => $this->property_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'availability' => $this->availability,
            'change_over' => $this->change_over,
            'max_stay' => $this->max_stay,
            'min_prior_notify' => $this->min_prior_notify,
            'min_stay' => $this->min_stay,
        ];
    }
}
