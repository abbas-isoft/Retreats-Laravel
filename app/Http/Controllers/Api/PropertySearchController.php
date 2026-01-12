<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;

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

            $query->whereRaw("LOWER(property_name) LIKE ?", ["%{$search}%"]);
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
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide a search keyword (q).',
                'data' => []
            ]);
        }

        $properties = PropertyContent::where('property_name', 'LIKE', "%{$query}%")
            ->get(['property_id', 'property_name']);

        if ($properties->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No matching properties found.',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $properties
        ]);
    }
}
