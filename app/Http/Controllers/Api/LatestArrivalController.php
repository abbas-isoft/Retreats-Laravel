<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use App\Http\Resources\PropertyContentResource;
use Illuminate\Http\Request;

class LatestArrivalController extends Controller
{
    public function index(Request $request)
{
    $filters = [
        'New Stays' => 'new_stays',
        'Without Hot Tubs' => 'without_hot_tubs',
        'Beach Stays' => 'beach_stays',
        'Luxury Stays' => 'luxury_stays',
        'Swimming' => 'SPORTS_SWIMMING', 
        'Hot Tub' => 'POOL_SPA_HOT_TUB',
       
    ];

    $selectedFilter = $request->query('filter');

    $query = PropertyContent::with(['images', 'rooms', 'beds', 'amenities'])->latest();

    if ($selectedFilter) {
        
        switch (strtolower($selectedFilter)) {
            case 'new_stays':
                $query->whereDate('created_at', '>=', now()->subDays(30));
                break;

            case 'without_hot_tubs':
                $query->where('has_hot_tub', 0);
                break;

            case 'beach_stays':
                $query->where('location', 'like', '%beach%');
                break;

            case 'luxury_stays':
                $query->where('property_type', 'luxury');
                break;

            default:
                // Now check if the filter matches an amenity name
                $query->whereHas('amenities', function ($q) use ($selectedFilter) {
                    $q->where('name', strtoupper($selectedFilter));
                });
                break;
        }
    }

    $arrivals = $query->take(10)->get();

    return response()->json([
        'status' => true,
        'message' => 'Latest arrivals fetched successfully.',
        'filters' => $filters,
        'selected_filter' => $selectedFilter,
        'latest_arrivals' => PropertyContentResource::collection($arrivals)->additional([
    'filter' => $selectedFilter
]),
    ]);
}
}


