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
    // Mapping for UI filters to internal DB keys or logic
    $filters = [
        'New Stays' => 'new_stays',
        'Without Hot Tubs' => 'without_hot_tubs',
        'Beach Stays' => 'beach_stays',
        'Luxury Stays' => 'luxury_stays',
        'swimming' => 'SPORTS_SWIMMING',
        'Hot Tub' => 'POOL_SPA_HOT_TUB',
    ];

    $selectedFilterRaw = $request->query('filter');
    $selectedFilter = strtolower($selectedFilterRaw);  // normalize input

    $query = PropertyContent::with(['images', 'rooms', 'beds', 'amenities'])->latest();

    if ($selectedFilter) {
        // Map the UI filter (like 'swimming') to the actual DB key (like 'SPORTS_SWIMMING')
        $mappedFilter = $filters[$selectedFilter] ?? $selectedFilterRaw;

        switch (strtolower($mappedFilter)) {
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
                // Handle it as an amenity name
                $query->whereHas('amenities', function ($q) use ($mappedFilter) {
                    $q->where('name', strtoupper($mappedFilter));
                });
                break;
        }
    }

    $arrivals = $query->take(10)->get();

    return response()->json([
        'status' => true,
        'message' => 'Latest arrivals fetched successfully.',
        'filters' => $filters,
        'selected_filter' => $selectedFilterRaw,
        'latest_arrivals' => PropertyContentResource::collection($arrivals)->additional([
            'filter' => $selectedFilterRaw
        ]),
    ]);
}

}


