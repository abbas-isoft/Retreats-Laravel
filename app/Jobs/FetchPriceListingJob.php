<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Property;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;

class FetchPriceListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        // Loop through all accounts
        foreach (Account::all() as $account) {
            $properties = Property::where('account_id', $account->id)->get();

            foreach ($properties as $property) {
                $this->fetchAndSavePrice($property->id, $account->id);
            }
        }

        // Replace $this->info with Log::info
        Log::info(' Price configurations saved for all properties across all accounts.');
    }

    private function fetchAndSavePrice($propertyId, $accountId)
    {
        try {
            $response = Http::timeout(600)->withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get("https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}");

            if ($response->failed()) {
                Log::error("Failed to fetch price data for property ID: {$propertyId}");
                return;
            }

            $data = $response->json();
            $prices = $data['prices'] ?? null;

            if (!$prices) {
                Log::warning("No price data returned for property ID: {$propertyId}");
                return;
            }

            // Save Price Configuration with account_id
            $config = PriceConfiguration::updateOrCreate(
                ['property_id' => $propertyId],
                [
                    'account_id' => $accountId,
                    'currency' => $prices['currency'] ?? null,
                    'deposit_type' => Arr::get($prices, 'paymentSchedule.deposit.depositType', null),
                    'deposit_value' => Arr::get($prices, 'paymentSchedule.deposit.value', 0),
                    'security_deposit' => Arr::get($prices, 'paymentSchedule.securityDeposit.value', 0),
                    'balance_due_days' => Arr::get($prices, 'paymentSchedule.balanceDueDays', 0),
                ]
            );

            // Save Fees safely
            foreach ($prices['fees'] ?? [] as $fee) {
                PriceFee::updateOrCreate(
                    [
                        'price_configuration_id' => $config->id,
                        'name' => $fee['name'] ?? 'Unknown Fee',
                    ],
                    [
                        'type' => $fee['type'] ?? 'unknown',
                        'price' => $fee['price'] ?? 0,
                        'vat' => $fee['vat'] ?? 0,
                        'refundable' => $fee['refundable'] ?? false,
                        'hidden' => $fee['hidden'] ?? false,
                        'fee_payable_with' => $fee['feePayableWith'] ?? null,
                    ]
                );
            }

            // Save LOS Entries safely
            PriceLosEntry::where('price_configuration_id', $config->id)->delete();

            foreach ($prices['priceLos'] ?? [] as $entry) {
                if (!is_string($entry)) {
                    Log::warning("Skipping invalid LOS entry for property ID {$propertyId}");
                    continue;
                }

                $parts = explode(',', $entry);
                $date = $parts[0] ?? null;

                if (!$date) {
                    Log::warning("Skipping LOS entry with missing date for property ID {$propertyId}");
                    continue;
                }

                PriceLosEntry::create([
                    'price_configuration_id' => $config->id,
                    'date' => $date,
                    'length_of_stay_prices' => json_encode($entry),
                ]);
            }

            Log::info("✅ Price data saved for property ID: {$propertyId}");
        } catch (\Exception $e) {
            Log::error("Exception saving price for property ID {$propertyId}: " . $e->getMessage());
            // Do not re-throw, just skip to next property
        }
    }
}
