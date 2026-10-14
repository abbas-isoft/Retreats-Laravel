<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\PropertyContent;
use Illuminate\Http\Request;

class PropertyPageAmenitiesController extends Controller
{
    public function show($propertyId)
    {
        $property = PropertyContent::where('property_id', $propertyId)->first();
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        return response()->json(['status' => true, 'data' => [
            'property_id' => $property->property_id,
            'property_page_amenities' => $property->property_page_amenities,
            'available_amenities' => Amenity::select('id', 'name')->orderBy('name')->get(),
        ]]);
    }

    public function update(Request $request, $propertyId)
    {
        $property = PropertyContent::where('property_id', $propertyId)->first();
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $validated = $request->validate([
            'property_page_amenities' => 'present|nullable|array|list',
            'property_page_amenities.*' => 'required|integer|distinct|exists:amenities,id',
        ]);

        $ids = $validated['property_page_amenities'];
        $property->property_page_amenities = $ids === null ? null : array_map('intval', $ids);
        $property->save();

        return response()->json([
            'status' => true,
            'message' => 'Property page amenities saved successfully',
            'data' => [
                'property_id' => $property->property_id,
                'property_page_amenities' => $property->property_page_amenities,
            ],
        ]);
    }
}
