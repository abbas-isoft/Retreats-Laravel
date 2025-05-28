<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use App\Models\PaymentSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PriceListingController extends Controller
{
    public function index($property_id): View
    {
        $configurations = PriceConfiguration::where('property_id', $property_id)->with(['fees', 'losEntries', 'paymentSchedules'])->latest()->get();
        return view('price-listing', compact('configurations'));
    }

    public function fetch(): RedirectResponse
    {
        try {
            
            $accounts = Account::all();

            if ($accounts->isEmpty()) {
                return redirect()->back()->with('error', 'No accounts found to fetch pricing data.');
            }

            foreach ($accounts as $account) {
                $propertyId = $account->property_id;

                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                    'Accept' => 'application/json',
                ])->get("https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}");

                if ($response->failed()) {
                    Log::error("Failed to fetch price data for property ID: {$propertyId}");
                    continue;
                }

                $data = $response->json();

                // 🔁 Delete old PriceConfiguration (cascade deletes fees, los, etc.)
                $existing = PriceConfiguration::where('property_id', $propertyId)->first();
                if ($existing) {
                    $existing->delete(); // This will cascade delete related data
                }

                // ➕ Recreate fresh PriceConfiguration
                $configuration = PriceConfiguration::create([
                    'account_id' => $account->id,
                    'property_id' => $propertyId,
                    'currency' => $data['currency'] ?? null,
                    'deposit_value' => $data['depositValue'] ?? null,
                    'price_type' => $data['priceType'] ?? null,
                    'short_breaks' => $data['shortBreaks'] ?? null,
                    'price_precision' => $data['pricePrecision'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'balance_due_days' => $data['prices']['paymentSchedule']['balanceDueDays'] ?? null,
                    'deposit_type' => $data['prices']['paymentSchedule']['deposit']['depositType'] ?? null,
                    'security_deposit' => $data['prices']['paymentSchedule']['securityDeposit']['value'] ?? null,
                    'raw_data' => json_encode($data['prices'] ?? []),
                ]);

                // ➕ Fees
                if (isset($data['prices']['fees']) && is_array($data['prices']['fees'])) {
                    foreach ($data['prices']['fees'] as $fee) {
                        PriceFee::create([
                            'price_configuration_id' => $configuration->id,
                            'name' => $fee['name'] ?? null,
                            'type' => $fee['type'] ?? null,
                            'price' => $fee['price'] ?? null,
                            'vat' => $fee['vat'] ?? null,
                            'refundable' => $fee['refundable'] ?? null,
                            'hidden' => $fee['hidden'] ?? null,
                            'fee_payable_with' => $fee['feePayableWith'] ?? null,
                        ]);
                    }
                }

                // ➕ LOS
                if (isset($data['prices']['priceLos']) && is_array($data['prices']['priceLos'])) {
                    foreach ($data['prices']['priceLos'] as $entry) {
                        $parts = explode(',', $entry);
                        $date = array_shift($parts);
                        $losPrices = json_encode($parts);

                        PriceLosEntry::create([
                            'price_configuration_id' => $configuration->id,
                            'date' => $date,
                            'length_of_stay_prices' => $losPrices,
                        ]);
                    }
                }

                // ➕ Payment Schedule
                if (isset($data['prices']['paymentSchedule'])) {
                    $schedule = $data['prices']['paymentSchedule'];

                    PaymentSchedule::create([
                        'price_configuration_id' => $configuration->id,
                        'deposit_type' => $schedule['deposit']['depositType'] ?? null,
                        'deposit_value' => $schedule['deposit']['value'] ?? null,
                        'security_deposit_value' => $schedule['securityDeposit']['value'] ?? null,
                        'balance_due_days' => $schedule['balanceDueDays'] ?? null,
                    ]);
                }
                Log::info('Fetched API data:', $data);  ////

            }

            return redirect()->route('price.index')->with('success', 'Prices, fees, LOS, and payment schedules updated from API.');
        } catch (\Exception $e) {
            Log::error('Price fetch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
