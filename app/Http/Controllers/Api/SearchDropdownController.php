<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;

class SearchDropdownController extends Controller
{
    public function index(Request $request)
{
    $query = PropertyContent::with('listing');

    // ✅ Filter by City / Location (from property_contents table)
    if ($request->filled('city')) {
        $query->where('city', 'LIKE', '%' . $request->city . '%');
    }

    $properties = $query->get();

    $locations = $properties->map(function ($property) {
        return [
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'address_line' => $property->address_line,
            'city' => $property->city,
        ];
    })->unique('address_line')->values();

    $guest_capacity = $properties->map(function ($property) {
        return $property->listing ? $property->listing->maximum_guests : null;
    })
    ->filter()
    ->unique()
    ->map(fn ($item) => strval($item))
    ->values();

    $amenities = $properties->pluck('amenities')
        ->map(function ($item) {
            return is_string($item) ? json_decode($item, true) : $item;
        })
        ->flatten()
        ->unique()
        ->values();

    $dateAvailability = $properties->flatMap(function ($property) {
        return optional($property->propertyAvailability)
            ->availabilityCalendars
            ? $property->propertyAvailability->availabilityCalendars->map(function ($calendar) {
                return [
                    'start_date' => $calendar->start_date,
                    'end_date'   => $calendar->end_date,
                ];
            })->toArray()
            : [];
    })
    ->unique(fn ($item) => $item['start_date'].'|'.$item['end_date'])
    ->values();

    return response()->json([
        'status' => true,
        'message' => '',
        'data' => [
            'locations' => $locations,
            'dateAvailability' => $dateAvailability,
            'guest_capacity' => $guest_capacity,
            'amenities' => $amenities,
        ]
    ]);
}

}