<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PriceConfigurationResource;
use App\Models\PriceConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class PricesApiController extends Controller
{
    public function index($propertyId): JsonResponse
    {
        try {
            $configs = PriceConfiguration::with(['fees', 'losEntries', 'paymentSchedules'])
                ->where('property_id', $propertyId)
                ->get(); 

            if ($configs->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => "No price configuration data found for property ID: {$propertyId}",
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => PriceConfigurationResource::collection($configs), 
            ]);
        } catch (Exception $e) {
            Log::error("Price Configuration fetch failed for property ID {$propertyId}: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
