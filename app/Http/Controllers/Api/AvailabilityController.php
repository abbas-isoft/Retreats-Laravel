<?php

// namespace App\Http\Controllers\Api;

// use Carbon\Carbon;
// use App\Models\Account;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\Log;
// use App\Http\Controllers\Controller;
// use App\Models\PropertyAvailability;
// use Illuminate\Support\Facades\Http;
// use App\Http\Resources\AvailabilityResource;

// class AvailabilityController extends Controller
// {
//     public function index(): JsonResponse
//     {
//          return response()->json([
//             'availabilities' => AvailabilityResource::collection(PropertyAvailability::latest()->get()),
//         ]);
//     }
// }


namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\PropertyAvailability;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\AvailabilityResource;

class AvailabilityController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $availabilities = PropertyAvailability::latest()->get();

            if ($availabilities->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No availability data found.'
                ]);
            }

            return response()->json([
                'status' => true,
                'availability' => AvailabilityResource::collection($availabilities)
            ]);
        } catch (\Exception $e) {
            Log::error('Availability fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching availabilities.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}