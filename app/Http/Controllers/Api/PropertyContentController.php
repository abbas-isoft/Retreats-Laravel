<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyContentResource;
use App\Models\Property;
use App\Models\PropertyContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PropertyContentController extends Controller
{
    public function index($id): JsonResponse
    {
        $property = Property::findOrFail($id);

        return response()->json([
            'property-content' => PropertyContentResource::collection(PropertyContent::latest()->get()),
        ]);
    }

    public function fetch($id): JsonResponse
    {
        try {
            $property = Property::findOrFail($id);
            $propertyId = $property->propertyId;

            return response()->json([
                'status' => false,
                'message' => 'PropertyContent data updated successfully from API.',
            ]);

        } catch (\Exception $e) {
            Log::error('PropertyContent fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
