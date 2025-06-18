<?php

namespace App\Http\Controllers\Api;

use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Models\PropertyConfiguration;
use App\Http\Resources\ConfigurationResource;

class ConfigurationController extends Controller
{
    public function index($accountid): JsonResponse
    {
        try {
            $configurations = PropertyConfiguration::whereHas('property', function ($property) use($accountid): void {
                $property->where('account_id', $accountid);
            })->latest()->get();

            if ($configurations->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No configuration found for this Property ID.',
                ]);
            }

            return response()->json([
                'status' => true,
                'propertyConfigurations' => ConfigurationResource::collection($configurations),
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

    public function fetch($accountId): JsonResponse
    {
        try {
            $account = Account::find($accountId);

            if (! $account ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Configuration URL not found for this account.',
                ], 404);
            }

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/ConfigurationIndex/' . $account->id);

            $properties = $response->json();

            if (! isset($properties['properties'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'No configuration data found from API.',
                ], 404);
            }

            foreach ($properties['properties'] as $property) {
                PropertyConfiguration::updateOrCreate(
                    [
                        'property_id' => $property['propertyId'],
                    ],
                    [
                        'configurationContentUrl' => $property['configurationContentUrl'] ?? null,
                        'last_updated' => $property['lastUpdated'] ?? null,
                    ]
                );
            }

            $updatedConfigurations = PropertyConfiguration::latest()->get();

            return response()->json([
                'status' => true,
                'message' => 'Configurations fetched and updated successfully.',
                'data' => ConfigurationResource::collection($updatedConfigurations),
            ]);
        } catch (\Exception $e) {
            \Log::error('Configuration fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching configuration.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
