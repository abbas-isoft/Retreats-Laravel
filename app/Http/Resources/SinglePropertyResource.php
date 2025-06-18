<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SinglePropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'property_id' => $this->property_id,
            'property_url' => $this->property_url,
            'customer_property_id' => $this->customer_property_id,
            'card_payment_forms' => $this->card_payment_forms,
            'cancellation_policy' => $this->cancellation_policy,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'children_allowed' => $this->children_allowed,
            'max_adults' => $this->max_adults,
            'max_guests' => $this->max_guests,
            'max_children' => $this->max_children,
            'merchant_name' => $this->merchant_name,
            'card_code' => $this->card_code,
            'card_type' => $this->card_type,
            'pets_allowed' => $this->pets_allowed,
            'smoking_allowed' => $this->smoking_allowed,
            'allow_bookings' => $this->allow_bookings,
            'allow_enquiries' => $this->allow_enquiries,
        ];
    }
}
