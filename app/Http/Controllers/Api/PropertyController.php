<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Models\Account;
use App\Models\Property;
use App\Jobs\FetchPropertyJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\PropertyResource;

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

    public function fetch($accountId): JsonResponse
    {
        try {

            $account = Account::find($accountId);

            if (! $account) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account not found with ID: '.$accountId,
                ], 404);
            }

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/ContentIndex/'.$account->id);

            $data = $response->json();

            if (! isset($data['properties'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'No properties data found from API.',
                ], 404);
            }

            foreach ($data['properties'] as $property) {
                Property::updateOrCreate(
                    ['id' => $property['propertyId']],
                    [
                        'account_id' => $account->id,
                        'active' => $property['active'] ?? null,
                        'listingUrl' => $property['listingUrl'] ?? null,
                    ]
                );
            }

            FetchPropertyJob::dispatch($account);

            $updatedProperties = Property::where('account_id', $account->id)->latest()->get();

            return response()->json([
                'status' => true,
                'message' => 'Properties fetched, updated & job dispatched successfully.',
                'data' => PropertyResource::collection($updatedProperties),
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
