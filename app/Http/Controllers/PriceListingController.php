<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PriceConfiguration;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class PriceListingController extends Controller
{
    public function index(): View
    {
        $configurations = PriceConfiguration::latest()->get();

        return view('price-listing', [
            'configurations' => $configurations,
        ]);
    }

    public function fetch(): RedirectResponse
    {
        try {
            $accounts = Account::all();

            if ($accounts->isEmpty()) {
                return back()->with('error', 'No accounts found.');
            }

            foreach ($accounts as $account) {
                if (!$account->property_id) {
                    continue;
                }

                $propertyId = $account->property_id;

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}?propertyLastUpdated=2025-04-29T04%3A35%3A08Z",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                        'Accept: application/json',
                    ],
                ]);

                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);

                if ($httpCode !== 200 || empty($response)) {
                    Log::warning("API error for property {$propertyId}", ['code' => $httpCode, 'response' => $response]);
                    continue;
                }

                $data = json_decode($response, true);

                if (!isset($data['prices'])) {
                    Log::warning("No 'prices' key found in response for property {$propertyId}", ['response' => $response]);
                    continue;
                }

                $prices = $data['prices'];
                $paymentSchedule = $prices['paymentSchedule'] ?? [];
                $deposit = $paymentSchedule['deposit'] ?? [];
                $securityDeposit = $paymentSchedule['securityDeposit'] ?? [];

                // ✅ Update or create using both account_id and property_id as keys
                PriceConfiguration::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'property_id' => $data['propertyId'],
                    ],
                    [
                        'currency' => $prices['currency'] ?? null,
                        'deposit_type' => $deposit['depositType'] ?? null,
                        'deposit_value' => $deposit['value'] ?? null,
                        'balance_due_days' => $paymentSchedule['balanceDueDays'] ?? null,
                        'security_deposit' => $securityDeposit['value'] ?? null,
                        'last_updated' => Carbon::now()->toDateTimeString(),
                        'raw_data' => json_encode($data),
                    ]
                );

                Log::info("Price data updated from API for property ID {$propertyId}");
            }

            return redirect()->route('price.index')->with('success', 'Live price data updated successfully.');

        } catch (\Exception $e) {
            Log::error('Price fetch failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
