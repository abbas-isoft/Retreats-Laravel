<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityResource;
use App\Jobs\FetchAvailabilityJob;
use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $availabilities = PropertyAvailability::latest()->get();

            if ($availabilities->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No availability data found.',
                ]);
            }

            return response()->json([
                'status' => true,
                'availability' => AvailabilityResource::collection($availabilities),
            ]);
        } catch (Exception $e) {
            Log::error('Availability fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching availabilities.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function fetch(): JsonResponse
    {
        try {
            $accounts = Account::all();

            if ($accounts->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No accounts found to fetch availability data.',
                ]);
            }

            foreach ($accounts as $account) {
                // Dispatch job for each account
                FetchAvailabilityJob::dispatch($account);
            }
            foreach ($accounts as $account) {
                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                ])->get('https://api.supercontrol.co.uk/v3/properties/AvailabilityIndex/'.$account->accountId);

                $data = $response->json();

                if (! isset($data['properties'])) {
                    continue;
                }

                foreach ($data['properties'] as $property) {
                    PropertyAvailability::updateOrCreate(
                        ['property_id' => $property['propertyId']],
                        [
                            'account_id' => $account->id,
                            'listing_url' => $property['listingUrl'] ?? null,
                            'last_updated' => Carbon::parse($property['lastUpdated'])->toDateTimeString(),
                        ]
                    );
                }
            }

            $updated = PropertyAvailability::latest()->get();

            return response()->json([
                'status' => true,
                'message' => 'Availability data fetched and updated successfully.',
                'data' => AvailabilityResource::collection($updated),
            ]);
        } catch (Exception $e) {
            Log::error('Live Availability fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching availability.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
