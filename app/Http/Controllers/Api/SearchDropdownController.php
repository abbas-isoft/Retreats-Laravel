<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;

class SearchDropdownController extends Controller
{
    public function index()
    {
        $properties = PropertyContent::all();

        $locations = $properties->pluck('location_description')->unique()->values();

        $guestCapacities = $properties->map(function ($property) {
    return $property->listing->maximum_guests ?? null;
})->filter()->unique()->values();

        $amenities = $properties->pluck('amenities')
            ->map(function ($item) {
                if (is_string($item)) {
                    return json_decode($item, true);
                }
                return $item;
            })
            ->flatten()
            ->unique()
            ->values();

        $propertyNames = $properties->pluck('property_name')->unique()->values();

       $dateAvailability = $properties->map(function ($property) {
    return optional($property->propertyAvailability)
        ->availabilityCalendars
        ? $property->propertyAvailability->availabilityCalendars
            ->map(function ($calendar) {
                return [
                    'start_date' => $calendar->start_date,
                    'end_date'   => $calendar->end_date,
                ];
            })
            ->toArray()
        : [];
})->flatten(1)->values();

        return response()->json([
            'status' => true,
            'message' => '',
            'data' => [
                'location_description' => $locations,
                'dateAvailability' => $dateAvailability,
                'guest_capacity' => $guestCapacities,
                'amenities' => $amenities,
                'property_name' => $propertyNames,
            ]
        ]);
    }
}
