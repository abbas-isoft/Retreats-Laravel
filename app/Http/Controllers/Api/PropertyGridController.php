<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;


class PropertyGridController extends Controller
{
    public function index(Request $request)
    {
        $query = PropertyContent::with(['images', 'listing']);

        
        if ($request->filled('location_description')) {
            $query->where('location_description', 'LIKE', '%' . $request->location . '%');
        }

        
        if ($request->filled('guest_capacity')) {
            $query->whereHas('listing', function ($q) use ($request) {
                $q->where('maximum_guests', '>=', $request->guest_capacity);
            });
        }

        
        if ($request->filled('amenities')) {
            $amenities = explode(',', $request->amenities);
            foreach ($amenities as $amenity) {
                $query->whereHas('amenities', function ($q) use ($amenity) {
                    $q->where('name', $amenity);
                });
            }
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
            // agar amenities string hai to array banado
            if (is_string($property->amenities)) {
                $property->amenities = json_decode($property->amenities, true);
            }
            return $property;
        }),
    ],
]);
    }
}

