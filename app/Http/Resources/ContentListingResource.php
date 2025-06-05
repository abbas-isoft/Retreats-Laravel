<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentListingResource extends JsonResource
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
        'accepted_payment_forms' => $this->accepted_payment_forms,
        'cancellation_policy' => $this->cancellation_policy,
        'check_in_time' => $this->check_in_time,
        'check_out_time' => $this->check_out_time,
        'children_allowed' => $this->children_allowed,
        'maximum_adults' => $this->maximum_adults,
        'maximum_guests' => $this->maximum_guests,
        'maximum_children' => $this->maximum_children,
        'merchant_name' => $this->merchant_name,
        'pets_allowed' => $this->pets_allowed,
        'smoking_allowed' => $this->smoking_allowed,
        'allow_bookings' => $this->allow_bookings,
        'allow_enquiries' => $this->allow_enquiries,
        ];

    }
}
