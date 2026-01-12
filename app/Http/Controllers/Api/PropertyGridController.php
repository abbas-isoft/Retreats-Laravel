<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PropertyGridController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyContent::query()
            ->with(['images', 'regions', 'amenities', 'content_listings', 'propertyAvailability.availabilityCalendars'])
            ->where('active', 1);

        // Collection filter
        if ($request->filled('collection_id')) {
            $query->join('collection_property', 'property_contents.id', '=', 'collection_property.property_id')
                ->where('collection_property.collection_id', $request->collection_id)
                ->select('property_contents.*');
        }

        // Region ID filter
        if ($request->filled('region_id')) {
            $regionIds = is_array($request->region_id)
                ? $request->region_id
                : explode(',', $request->region_id);

            $query->whereIn('id', function ($sub) use ($regionIds) {
                $sub->select('pc.id')
                    ->from('property_contents as pc')
                    ->join('properties as p', 'p.id', '=', 'pc.property_id')
                    ->join('region_property as rp', 'rp.property_id', '=', 'p.id')
                    ->whereIn('rp.region_id', $regionIds);
            });
        }

        // Address line filter
        if ($request->filled('address_line')) {
            $query->where('address_line', 'LIKE', '%' . $request->address_line . '%');
        }

        // Guest capacity filter
        if ($request->filled('guest_capacity')) {
            $query->whereHas('content_listings', function ($q) use ($request) {
                $q->where('maximum_guests', '>=', $request->guest_capacity);
            });
        }

        // ✅ Amenities filter
        if ($request->filled('amenities')) {
            $amenities = is_array($request->amenities)
                ? $request->amenities
                : explode(',', $request->amenities);
            $amenities = array_map('trim', $amenities);

            $query->whereHas('amenities', function ($q) use ($amenities) {
                $q->whereIn('name', $amenities);
            });
        }

        // City filter
        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%' . $request->city . '%');
        }

        // Region name filter
        if ($request->filled('region')) {
            $query->whereIn('property_contents.property_id', function ($sub) use ($request) {
                $sub->select('rp.property_id')
                    ->from('region_property as rp')
                    ->join('regions as r', 'r.id', '=', 'rp.region_id')
                    ->where('r.active', 1)
                    ->where('r.name', 'LIKE', '%' . $request->region . '%');
            });
        }

        // Search by property name
        if ($request->filled('search')) {
            $query->where('property_name', 'LIKE', '%' . $request->search . '%');
        }

        // Date availability filter
        if ($request->filled('date_availability.start_date') && $request->filled('date_availability.end_date')) {
            $checkIn  = Carbon::parse($request->date_availability['start_date']);
            $checkOut = Carbon::parse($request->date_availability['end_date']);

            $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut) {
                $q->where('start_date', '<=', $checkIn)
                  ->where('end_date', '>=', $checkOut);
            });
        }

        // Booking conflict filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDoesntHave('bookings', function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                          ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
                });
            });
        }

        $properties = $query->get();

        // ✅ Clean JSON Response
        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'data' => [
                'properties' => $properties->map(function ($property) {
                    // Decode amenities if stored as JSON
                    if (is_string($property->amenities)) {
                        $property->amenities = json_decode($property->amenities, true);
                    }
                    if (!is_array($property->amenities)) {
                        $property->amenities = [];
                    }

                    return [
                        'property_id' => $property->id,
                        'property_name' => $property->property_name,
                        'city' => $property->city,
                        'address_line' => $property->address_line,
                        'latitude' => $property->latitude,
                        'longitude' => $property->longitude,
                        'location_description' => Str::limit($property->location_description, 50),
                        'images' => $property->images ?? [],
                        'amenities' => $property->amenities,
                        'guest_capacity' => optional($property->content_listings)->maximum_guests ?? null,
                    ];
                }),
            ],
        ]);
    }
}
