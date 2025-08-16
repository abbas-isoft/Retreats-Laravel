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
        'New Stays' => 'New Stays',
        'Without Hot Tubs' => 'Without Hot Tubs',
        'Beach Stays' => 'Beach Stays',
        'Luxury Stays' => 'Luxury Stays',
        'swimming' => 'swimming',
        'Hot Tub' => 'Hot Tub',
    ];

    $selectedFilterRaw = $request->query('filter');
    $selectedFilter = strtolower($selectedFilterRaw);

    $query = PropertyContent::with(['images', 'rooms', 'beds', 'amenities'])->latest();

    if ($selectedFilter) {
        $mappedFilter = $filters[$selectedFilter] ?? $selectedFilterRaw;

        switch ($mappedFilter) {
            case 'New Stays':
                $query->whereDate('created_at', '>=', now()->subDays(30));
                break;

            case 'Without Hot Tubs':
                // ✅ Properties that DO NOT have the amenity 'POOL_SPA_HOT_TUB'
                $query->whereDoesntHave('amenities', function ($q) {
                    $q->where('name', 'POOL_SPA_HOT_TUB');
                });
                break;

            case 'Beach Stays':
    $query->where(function ($q) {
        $q->whereRaw('LOWER(location_description) LIKE ?', ['%beach%'])
          ->orWhereRaw('LOWER(location_description) LIKE ?', ['%seaside%'])
          ->orWhereRaw('LOWER(location_description) LIKE ?', ['%coast%']);
    });
    break;

          case 'Luxury Stays':
    $query->where('property_type', 'LIKE', '%cottage%');
    break;

     case 'swimming':
                    $query->whereHas('amenities', function ($q) {
                        $q->where('name', 'SPORTS_SWIMMING');
                    });
                    break;

            default:
                // ✅ Properties that HAVE the specified amenity
                $query->whereHas('amenities', function ($q) use ($mappedFilter) {
                    $q->where('name', strtoupper($mappedFilter));
                });
                break;

                 case 'Hot Tub':
                    $query->whereHas('amenities', function ($q) {
                        $q->where('name', 'POOL_SPA_HOT_TUB');
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
