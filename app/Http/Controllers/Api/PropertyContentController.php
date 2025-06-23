<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyContentResource;
use App\Models\Property;
use App\Models\PropertyContent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PropertyContentController extends Controller
{
    public function index($propertyId): JsonResponse
    {
        try {
            // Check if the property exists
            $property = Property::find($propertyId);

            if (! $property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ]);
            }

            // Fetch property content filtered by property_id, ordered latest first
            $contents = PropertyContent::where('property_id', $propertyId)
                ->latest()
                ->get();

            // If no property content found, return empty array with success message
            if ($contents->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No Property Content data found.',
                    'property_content' => [],
                ]);
            }

            // Return collection of property content resources
            return response()->json([
                'status' => true,
                'message' => 'Property content fetched successfully.',
                'property_content' => PropertyContentResource::collection($contents),
            ]);
        } catch (Exception $e) {
            Log::error('Property content fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching property content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
