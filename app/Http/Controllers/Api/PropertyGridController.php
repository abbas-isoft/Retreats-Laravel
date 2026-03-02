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
        $query = \App\Models\PropertyContent::query()
            ->with('images')
            ->where('active', 1);

        if ($request->filled('collection_id')) {
            $query->join('collection_property', 'property_contents.id', '=', 'collection_property.property_id') // ✅ correct join
                ->where('collection_property.collection_id', $request->collection_id)
                ->select('property_contents.*');
        }

        $query = PropertyContent::query()
            ->with(['images', 'regions'])
            ->where('active', 1);

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

        if ($request->filled('address_line')) {
            $query->where('address_line', 'LIKE', '%' . $request->address_line . '%');
        }


        if ($request->filled('guest_capacity')) {
            $query->whereHas('content_listings', function ($q) use ($request) {
                $q->where('maximum_guests', '>=', $request->guest_capacity);
            });
        }

        if ($request->filled('amenities')) {

            $amenities = is_array($request->amenities)
                ? $request->amenities
                : explode(',', $request->amenities);

            $amenities = array_map('trim', $amenities);

            foreach ($amenities as $amenity) {

                $query->where(function ($q) use ($amenity) {

                    // Special case for swimming pool
                    if (strtolower($amenity) === 'swimming pool') {

                        $q->where('amenities', 'LIKE', '%Indoor Swimming pool%')
                            ->orWhere('amenities', 'LIKE', '%Outdoor Swimming pool%');
                    } else {

                        $q->where('amenities', 'LIKE', '%"' . $amenity . '"%')
                            ->orWhere('amenities', 'LIKE', '%' . $amenity . '%');
                    }
                });
            }
        }


        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%' . $request->city . '%');
        }

        if ($request->filled('region')) {

            $query->whereIn('property_contents.property_id', function ($sub) use ($request) {

                $sub->select('rp.property_id')
                    ->from('region_property as rp')
                    ->join('regions as r', 'r.id', '=', 'rp.region_id')
                    ->where('r.active', 1)
                    ->where('r.name', 'LIKE', '%' . $request->region . '%');
            });
        }



        if ($request->filled('search')) {
            $query->where('property_name', 'LIKE', '%' . $request->search . '%');
        }


        // if ($request->filled('date_availability')) {
        //     $query->whereHas('propertyAvailability', function ($q) use ($request) {
        //         $date = Carbon::parse($request->date_availability);
        //         $q->where('updated_at', '<=', $date);
        //     });
        // }

        //    if ($request->filled(['check_in', 'check_out'])) {
        //     $checkIn  = Carbon::parse($request->check_in);
        //     $checkOut = Carbon::parse($request->check_out);

        //     $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut) {
        //         $q->where('start_date', '<=', $checkIn)
        //           ->where('end_date', '>=', $checkOut)
        //           ->whereRaw("
        //               SUBSTRING(availability, DATEDIFF(?, start_date) + 1, DATEDIFF(?, ?) + 1) 
        //               = REPEAT('Y', DATEDIFF(?, ?) + 1)
        //           ", [
        //               $checkIn->format('Y-m-d'),   // start offset
        //               $checkOut->format('Y-m-d'),  // length till checkout
        //               $checkIn->format('Y-m-d'),   // length calc
        //               $checkOut->format('Y-m-d'), 
        //               $checkIn->format('Y-m-d')
        //           ]);
        //     });
        // }



        if ($request->filled('date_availability.start_date') && $request->filled('date_availability.end_date')) {
            $checkIn  = Carbon::parse($request->date_availability['start_date']);
            $checkOut = Carbon::parse($request->date_availability['end_date']);

            $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut) {
                $q->where('start_date', '<=', $checkIn)
                    ->where('end_date', '>=', $checkOut);
            });
        }


        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDoesntHave('bookings', function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
                });
            });
        }

        $properties = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'data' => [
                'properties' => $properties->map(function ($property) {
                    if (is_string($property->amenities)) {
                        $property->amenities = json_decode($property->amenities, true);
                    }
                    $property->location_description = Str::limit($property->location_description, 50);
                    return $property;
                }),
            ],
        ]);
    }
}
