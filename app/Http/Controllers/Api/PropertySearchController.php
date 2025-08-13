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
}
