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
            $property = Property::find($propertyId);

            if (!$property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ]);
            }

            $contentListings = ContentListing::where('property_id', $propertyId)->latest()->get();

            return response()->json([
                'status' => true,
                'content-listing' => ContentListingResource::collection($contentListings),
            ]);

        } catch (Exception $e) {
            Log::error('Content Listing fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
