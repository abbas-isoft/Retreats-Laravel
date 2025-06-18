<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConfigurationResource;
use App\Models\PropertyConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
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
                    'property_id_checked' => $id,
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => ConfigurationResource::collection($configurations),
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
            $account = \App\Models\Account::find($accountId);

            if (! $account || ! $account->configurationIndexUrl) {
                return response()->json([
                    'status' => false,
                    'message' => 'Configuration URL not found for this account.',
                ], 404);
            }

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get($account->configurationIndexUrl);

            $properties = $response->json();

            if (! isset($properties['properties'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'No configuration data found from API.',
                ], 404);
            }

            foreach ($properties['properties'] as $property) {
                if (! isset($property['cardCode'])) {
                    continue; // Skip if no cardCode
                }

                // Overwrite DB with live API data
                \App\Models\PropertyConfiguration::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'property_id' => $property['id'] ?? null,
                    ],
                    [
                        'card_code' => $property['cardCode'] ?? null,
                        'card_type' => $property['cardType'] ?? null,
                        'cancellation_policy' => $property['cancellationPolicy'] ?? null,
                        'merchant_name' => $property['merchantName'] ?? null,
                        'minimum_age' => $property['minimumAge'] ?? null,
                        'rental_agreement_url' => $property['rentalAgreementUrl'] ?? null,
                        'configurationContentUrl' => $property['configurationContentUrl'] ?? null,
                        'last_updated' => $property['lastUpdated'] ?? null,
                    ]
                );
            }

            $updatedConfigurations = \App\Models\PropertyConfiguration::where('account_id', $account->id)->latest()->get();

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
