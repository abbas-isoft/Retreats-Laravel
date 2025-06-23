<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Property;
use App\Models\ContentListing;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContentListingResource;
use Illuminate\Support\Facades\Log;

class ContentListingController extends Controller
{
    public function index($propertyId): JsonResponse
    {
        try {
            // Check if the property exists in DB
            $property = Property::find($propertyId);

            if (! $property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ]);
            }

            // Fetch content listings filtered by property_id, order by latest first
            $contentListings = ContentListing::where('property_id', $propertyId)
                ->latest()
                ->get();

            // If no content listing found, return empty array with success message
            if ($contentListings->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No content listing data found.',
                    'content-listing' => [],
                ]);
            }

            // Return content listing resource collection
            return response()->json([
                'status' => true,
                'content-listing' => ContentListingResource::collection($contentListings),
            ]);
        } catch (Exception $e) {
            Log::error('Content Listing fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching Content Listing.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
