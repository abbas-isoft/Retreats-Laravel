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
        'swimming' => 'SPORTS_SWIMMING',
        'hot_tub' => 'POOL_SPA_HOT_TUB',
    ];

    $selectedFilterRaw = $request->query('filter');
    $selectedFilter = strtolower($selectedFilterRaw);

    $query = PropertyContent::with(['images', 'rooms', 'beds', 'amenities'])->latest();

    if ($selectedFilter) {
        $mappedFilter = $filters[$selectedFilter] ?? $selectedFilterRaw;

        switch ($mappedFilter) {
            case 'new_stays':
                $query->whereDate('created_at', '>=', now()->subDays(30));
                break;

            case 'without_hot_tubs':
                // ✅ Properties that DO NOT have the amenity 'POOL_SPA_HOT_TUB'
                $query->whereDoesntHave('amenities', function ($q) {
                    $q->where('name', 'POOL_SPA_HOT_TUB');
                });
                break;

            case 'beach_stays':
    $query->where(function ($q) {
        $q->whereRaw('LOWER(location_description) LIKE ?', ['%beach%'])
          ->orWhereRaw('LOWER(location_description) LIKE ?', ['%seaside%'])
          ->orWhereRaw('LOWER(location_description) LIKE ?', ['%coast%']);
    });
    break;

          case 'luxury_stays':
    $query->where('property_type', 'LIKE', '%cottage%');
    break;

            default:
                // ✅ Properties that HAVE the specified amenity
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
