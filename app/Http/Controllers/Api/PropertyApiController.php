<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;

class PropertyApiController extends Controller
{
    public function getProperties(Request $request)
    {
        // Fetch property contents with listings and images
        $properties = PropertyContent::with(['listing', 'images'])->get();

        $result = $properties->map(function ($property) {
            $listing = $property->listing;

            return [
                'property_id'     => $property->property_id,
                'property_name'   => $property->property_name ?? null,
                'city'            => $property->city ?? null,
                'check_in'        => $listing->check_in_time ?? null,
                'check_out'       => $listing->check_out_time ?? null,
                'maximum_guests'  => $listing->maximum_guests ?? null,
                'images'          => $property->images->pluck('url')->all(), // only URLs
            ];
        });

        return response()->json([
            'status' => true,
            'properties' => $result
        ]);
    }
}
