<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyContentResource extends JsonResource
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
            'customer_property_id' => $this->customer_property_id,
            'active' => $this->active,
            'property_name' => $this->property_name,
            'subcaption' => $this->subcaption,
            'description' => $this->description,
            'accommodations_summary' => $this->accommodations_summary,
            'location_description' => $this->location_description,
            'changeover' => $this->changeover,
            'second' => $this->second,
            'address_line' => $this->address_line,
            'city' => $this->city,
            'country' => $this->country,
            'post_code' => $this->post_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'property_type' => $this->property_type,
            'registration_number' => $this->registration_number,
            'epcRating' => $this->epcRating,
            'bedroom_count' => $this->bedroom_count,
            'bathroom_count' => $this->bathroom_count,
            'area' => $this->area,
            'maximum_guests' => $this->listing->maximum_guests ?? 0,
            'reviews' => $this->reviews,
            'content_listings' => ContentListingResource::collection($this->content_listings),
            // 'similar_properties' => $this->similar_properties,
            'area_unit' => $this->area_unit,
            // 'amenities ' => $this->amenities, 
            'amenities' => $this->when(
                isset($this->filter),
                function () {
                    // JSON string ko array/collection me convert karo
                    $amenities = is_string($this->amenities) ? collect(json_decode($this->amenities, true)) : $this->amenities;

                    return $amenities->filter(function ($amenity) {
                        return strtoupper($amenity['name'] ?? $amenity) === strtoupper($this->filter);
                    })->values();
                },
                // Agar filter nahi hai, pura amenities array/collection return karo
                is_string($this->amenities) ? json_decode($this->amenities, true) : $this->amenities
            ),
            'admin_amenities' => is_string($this->amenities) ? json_decode($this->amenities, true) : $this->amenities,
            'images' => PropertyContentImageResource::collection($this->images),
            'rooms' => PropertyRoomResource::collection($this->rooms),
            // 'beds' => $this->beds ? PropertyRoomBedsResource::collection($this->beds) : null,
            // 'beds' => PropertyRoomBedsResource::collection($this->beds),
            'beds' => PropertyRoomBedsResource::collection($this->beds ?? collect()),

        ];
    }
}
