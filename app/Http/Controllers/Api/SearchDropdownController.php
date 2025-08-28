<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;

class SearchDropdownController extends Controller
{
    public function index()
    {
        $properties = PropertyContent::with('listing')->get();

         
        $locations = $properties->map(function ($property) {
    return [
        'latitude' => $property->latitude,
        'longitude' => $property->longitude,
        'address_line' => $property->address_line, 
    ];
})->unique('address_line')->values();


        $guest_capacity = $properties->map(function ($property) {
    return $property->listing ? $property->listing->maximum_guests : null;
})
->filter() // remove nulls
->unique()
->map(fn($item) => strval($item)) // string conversion
->values();

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
                'locations' => $locations,
                'dateAvailability' => $dateAvailability,
                'guest_capacity' => $guest_capacity,
                'amenities' => $amenities,
                // 'property_name' => $propertyNames,
            ]
        ]);
    }
}
