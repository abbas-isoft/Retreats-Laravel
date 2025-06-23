<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    public function index($accountid): JsonResponse
    {
        try {
            $properties = Property::where('account_id', $accountid)->latest()->get();

            if ($properties->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No properties found for this Account ID.',
                ]);
            }

            return response()->json([
                'status' => true,
                'properties' => PropertyResource::collection($properties),
            ]);
        } catch (Exception $e) {
            Log::error('Property fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching properties.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
