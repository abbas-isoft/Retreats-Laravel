<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AvailabilityRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class PropertyAvailabilityRecordsController extends Controller
{
    public function index()
{
    return view('availabilityrecord', [
        'records' => AvailabilityRecord::all()
    ]);
}

    public function fetch()
    {
        try {
            $accounts = Account::all();

            if ($accounts->isEmpty()) {
                return redirect()->back()->with('error', 'No accounts found.');
            }

            foreach ($accounts as $account) {
                Log::info("Fetching availability for accountId: {$account->accountId}");

                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                    'Accept' => 'application/json',
                    'Cookie' => 'incap_ses_1219_3112=...; nlbi_3112=...; visid_incap_3112=...',
                ])->get("https://api.supercontrol.co.uk/v3/Properties/Availability/{$account->accountId}");

                if ($response->failed()) {
                    Log::error("Failed to fetch for accountId: {$account->accountId}");
                    continue;
                }

                $data = $response->json();

                if (!isset($data['propertyAvailability']) || !isset($data['propertyId'])) {
                    Log::warning("Missing availability data for accountId: {$account->accountId}");
                    continue;
                }

                $availability = $data['propertyAvailability'];

                AvailabilityRecord::updateOrCreate(
                    ['property_id' => $data['propertyId']],
                    [
                        'start_date'       => $availability['startDate'] ?? null,
                        'end_date'         => $availability['endDate'] ?? null,
                        'availability'     => $availability['availability'] ?? null,
                        'change_over'      => $availability['changeOver'] ?? null,
                        'max_stay'         => $availability['maxStay'] ?? null,
                        'min_stay'         => $availability['minStay'] ?? null,
                        'min_prior_notify' => $availability['minPriorNotify'] ?? null,
                    ]
                );

                Log::info("Saved property_id: {$data['propertyId']}");
            }

            return redirect()->route('availability-records.index')
                ->with('success', 'Availability records updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error fetching availability: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Fetch failed: ' . $e->getMessage());
        }
    }
}
