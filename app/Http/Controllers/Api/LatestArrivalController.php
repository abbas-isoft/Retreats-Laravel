<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PropertyContent;
use App\Http\Resources\LatestArrivalResource;
use Illuminate\Support\Facades\DB;

class LatestArrivalController extends Controller
{
    /**
     * GET /api/latest-arrivals?filter=new_stays|without_hot_tub|beach_stays|luxury_stays
     */
    public function index(Request $request)
{
    $filter = $request->query('filter'); // e.g. "swimming"

    $query = PropertyContent::with('images')->where('active', 1);

    $properties = $query->orderBy('created_at', 'desc')->get();

    // Apply partial, case-insensitive filter
    if ($filter) {
        $filterLower = strtolower($filter);

        $properties = $properties->filter(function ($property) use ($filterLower) {
            $amenities = json_decode($property->amenities, true);

            if (is_array($amenities)) {
                // Check if any amenity contains the filter word (case-insensitive)
                foreach ($amenities as $amenity) {
                    if (str_contains(strtolower($amenity), $filterLower)) {
                        return true;
                    }
                }
            }

            return false;
        })->values(); // Reindex
    }

    // Decode for response
    $properties->transform(function ($property) {
        $property->amenities = json_decode($property->amenities);
        return $property;
    });

    return response()->json([
        'status' => true,
        'data' => LatestArrivalResource::collection($properties),
        'checked_amenity' => $filter,
        'results_count' => $properties->count(),
    ]);
}
}
