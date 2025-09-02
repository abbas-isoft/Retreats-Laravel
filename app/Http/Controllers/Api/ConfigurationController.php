<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountConfigurationResource;
use App\Http\Resources\ConfigurationResource;
use App\Models\AccountConfiguration;
use App\Models\PropertyConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ConfigurationController extends Controller
{
    public function index($accountid): JsonResponse
    {
        try {
            $configurations = PropertyConfiguration::whereHas('property', function ($query) use ($accountid): void {
                $query->where('account_id', $accountid);
            })->latest()->get();

            $accountConfiguration = AccountConfiguration::where('account_id', $accountid)->first();

            if ($configurations->isEmpty() && ! $accountConfiguration) {
                return response()->json([
                    'status' => false,
                    'message' => 'No configuration found for this account.',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Configurations fetched and updated successfully.',
                'account_configuration' => $accountConfiguration
                    ? new AccountConfigurationResource($accountConfiguration)
                    : null,
                'properties' => ConfigurationResource::collection($configurations),
            ]);
        } catch (\Exception $e) {
            Log::error('Configuration fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
