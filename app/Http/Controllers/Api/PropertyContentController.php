<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyContentResource;
use App\Models\Property;
use App\Models\PropertyContent;
use App\Models\Collection;
use App\Models\PropertyContentImage;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PropertyContentController extends Controller
{

    public function index($propertyId): JsonResponse
    {
        try {
            $property = Property::find($propertyId);

            if (! $property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found',
                ]);
            }

            $contents = PropertyContent::where('property_id', $propertyId)
                ->latest()
                ->get();

            if ($contents->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No Property Content data found.',
                    'property_content' => [],
                ]);
            }

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

    // ✅ New method: Fetch all property locations for map display
    public function getLocations(): JsonResponse
    {
        try {
            $locations = PropertyContent::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get([
                    'id',
                    'property_name',
                    'location_description',
                    'latitude',
                    'longitude',
                    'city',
                    'country'
                ]);

            return response()->json([
                'status' => true,
                'message' => 'Map locations fetched successfully.',
                'data' => $locations,
            ]);
        } catch (Exception $e) {
            Log::error('Location fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching map locations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCollection($id): JsonResponse
    {
        // Find the Collection by its ID
        $collection = Collection::find($id);

        // If the Collection doesn't exist, return an error message
        if (!$collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found by ID',
            ], 404);
        }

        // Get all PropertyContentImage entries that belong to this collection
        $collectionRelatedImages = PropertyContentImage::where('property_content_id', $collection->id)->get();

        // Prepare the images data, replacing property_content_id with collection title
        $imagesWithTitle = $collectionRelatedImages->map(function ($image) use ($collection) {
            $image->collection_title = $collection->title;  // Add the title to the image data
            return $image;
        });

        return response()->json([
            'status' => true,
            'message' => 'Images related to the Collection: ' . $collection->title,
            'data' => $imagesWithTitle
        ], 200);
    }
}
