<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class PropertySearchController extends Controller
{
    public function search(Request $request)
    {
        $query = PropertyContent::query()
            ->with(['images', 'amenities']);
            // ->where('active', 1); // Uncomment if needed

        
        if ($request->filled('city')) {
            $query->whereRaw('LOWER(city) LIKE ?', ['%' . strtolower($request->city) . '%']);
        }

       
        if ($request->filled('location_description')) {
            $keywords = explode(' ', strtolower($request->location_description));

            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $word) {
                    $q->whereRaw("LOWER(location_description) LIKE ?", ["%{$word}%"]);
                }
            });
        }

        
        if ($request->filled('availability')) {
            $availability = $request->availability;
            if ($availability === 'true') {
                $query->whereHas('availabilityRelation', function ($q) {
                    $q->where('is_available', true); 
                });
            }
        }

        
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(property_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(description) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(address_line) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("LOWER(location_description) LIKE ?", ["%{$search}%"]);
            });
        }

       
        $properties = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'properties' => $properties
        ]);
    }

    public function GetPropertiessearch(Request $request)
{
    $query = strtolower(trim($request->input('q')));

    if (!$query) {
        return response()->json([
            'status' => false,
            'message' => 'Please provide a search keyword (q).',
            'data' => []
        ], 400);
    }

    $properties = PropertyContent::select(
            'property_id',
            DB::raw("
                TRIM(
                    REGEXP_REPLACE(property_name, '\\\\s*by\\\\s+group\\\\s*retreats?$', '')
                ) as property_name
            ")
        )
        ->whereRaw("
            LOWER(
                TRIM(
                    REGEXP_REPLACE(property_name, '\\\\s*by\\\\s+group\\\\s*retreats?$', '')
                )
            ) LIKE ?
        ", ["%{$query}%"])   // 🔥 INCLUDE MATCH
        ->limit(10)
        ->get();

    if ($properties->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No matching properties found.',
            'data' => []
        ], 404);
    }

    return response()->json([
        'status' => true,
        'data' => $properties
    ]);
}

}
