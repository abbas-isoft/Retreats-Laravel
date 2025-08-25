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

    if ($request->filled('date_availability')) {
    $query->whereHas('propertyAvailability', function ($q) use ($request) {
        $date = Carbon::parse($request->date_availability);

        $q->whereHas('availabilityCalendars', function ($query) use ($date) {
            // Check if the date is within the start and end date range
            $query->where('start_date', '<=', $date)
                  ->where('end_date', '>=', $date)
                  // Ensure availability contains "Y" for the requested date
                  ->where('availability', 'like', '%' . $date->format('Y-m-d') . '%');
        });
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
                'properties' => $properties,
            ],
        ]);
    }
}

