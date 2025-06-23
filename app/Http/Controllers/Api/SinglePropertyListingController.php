<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SinglePropertyResource;
use App\Models\Property;
use App\Models\SingleConfiguration;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SinglePropertyListingController extends Controller
{
    public function index($propertyId): JsonResponse
    {
        try {

            $property = Property::find($propertyId);

            if (! $property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found.',
                ]);
            }

            $configurations = SingleConfiguration::where('property_id', $propertyId)
                ->latest()
                ->get();

            if ($configurations->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No single property configuration data found.',
                ]);
            }

            return response()->json([
                'status' => true,
                'single_property_listing' => SinglePropertyResource::collection($configurations),
            ]);
        } catch (Exception $e) {
            Log::error('Single property configuration fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching single property configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
