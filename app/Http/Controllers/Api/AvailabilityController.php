<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityResource;
use App\Models\PropertyAvailability;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    public function index($accountId): JsonResponse
    {
        try {
            $availabilities = PropertyAvailability::where('account_id', $accountId)->latest()->get();

            if ($availabilities->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No availability data found for this account.',
                ]);
            }

            return response()->json([
                'status' => true,
                'availability' => AvailabilityResource::collection($availabilities),
            ]);
        } catch (Exception $e) {
            Log::error('Availability fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching availabilities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
