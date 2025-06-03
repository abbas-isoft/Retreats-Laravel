<?php

// namespace App\Http\Controllers\Api;

// use Carbon\Carbon;
// use App\Models\Account;
// use App\Models\Property;
// use Illuminate\Http\JsonResponse;
// use App\Http\Controllers\Controller;
// use App\Http\Resources\PropertyResource;

// class PropertyController extends Controller
// {
//     public function index($accountid): JsonResponse
//     {
//          return response()->json([
//             'properties' => PropertyResource::collection(Property::latest()->get()),
//         ]);
//     }

// }

namespace App\Http\Controllers\Api;

use App\Models\Property;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyResource;
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
                    'account_id_checked' => $accountid
                ]);
            }

            return response()->json([
                'status' => true,
                'Properties' => PropertyResource::collection($properties)
            ]);
        } catch (\Exception $e) {
            Log::error('Property fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching properties.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
