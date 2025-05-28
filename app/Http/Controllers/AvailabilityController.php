<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AvailabilityController extends Controller
{
    public function index()
    {
        return view('availability', [
            'availabilities' => PropertyAvailability::get(),
        ]);
    }

    public function fetch()
    {
        try {
            $accounts = Account::all();

            if ($accounts->isEmpty()) {
                return redirect()->back()->with('error', 'No accounts found to fetch availability.');
            }

            foreach ($accounts as $account) {
                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                    'Accept' => 'application/json',
                ])->get("https://api.supercontrol.co.uk/v3/Properties/AvailabilityIndex/{$account->accountId}");

                if ($response->failed()) {
                    Log::error("Failed to fetch availability for account: {$account->accountId}");
                    continue;
                }

                $data = $response->json();

                if (isset($data['properties'])) {
                    foreach ($data['properties'] as $item) {
                        $lastUpdated = Carbon::parse($item['lastUpdated'])->toDateTimeString();

                        PropertyAvailability::updateOrCreate(
                            ['property_id' => $item['propertyId']],
                            [
                                'account_id' => $account->id,
                                'listing_url' => $item['listingUrl'] ?? null,
                                'last_updated' => $lastUpdated,
                            ]    
                        );
                    }
                }
            }

            return redirect()->route('availability.index')
                ->with('success', 'Availability data updated successfully from API.');

        } catch (\Exception $e) {
            Log::error('Availability fetch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
