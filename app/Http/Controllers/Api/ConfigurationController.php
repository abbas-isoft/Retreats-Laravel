<?php

// namespace App\Http\Controllers\Api;

// use App\Models\Account;
// use App\Models\Property;
// use Illuminate\Http\JsonResponse;
// use Illuminate\View\View;
// use App\Http\Controllers\Controller;
// use App\Models\PropertyConfiguration;
// use Illuminate\Http\RedirectResponse;
// use App\Http\Resources\ConfigurationResource;
// use App\Http\Resources\PropertyConfigurationResource;


// class ConfigurationController extends Controller
// {
//     public function index($id): JsonResponse
//     {
//         return response()->json([
//             'configuration' => ConfigurationResource::collection(PropertyConfiguration::latest()->get()),
//         ]);
//     }

// }

namespace App\Http\Controllers\Api;

use App\Models\PropertyConfiguration;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConfigurationResource;
use Illuminate\Support\Facades\Log;

class ConfigurationController extends Controller
{
    public function index($id): JsonResponse
    {
        try {
            $configurations = PropertyConfiguration::where('account_id', $id)->latest()->get();

            if ($configurations->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No configuration found for this Property ID.',
                    'property_id_checked' => $id
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => ConfigurationResource::collection($configurations)
            ]);
        } catch (\Exception $e) {
            Log::error('Configuration fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching configuration.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

