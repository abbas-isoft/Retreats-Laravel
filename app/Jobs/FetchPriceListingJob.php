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

class FetchPriceListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle()
    {
        foreach (Account::all() as $account) {
            $properties = Property::where('account_id', $account->id)->get();

            foreach ($properties as $property) {
                try {
                    $this->fetchAndSavePrice($property->id, $account->id);
                } catch (\Exception $e) {
                    Log::error("❌ Error processing property {$property->id}: " . $e->getMessage());
                }
            }
        }

        Log::info('✅ Price configurations saved for all properties across all accounts.');
    }

    private function fetchAndSavePrice($propertyId, $accountId)
    {
        $response = Http::withHeaders([
            'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
        ])->get("https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}");

        if ($response->failed()) {
            Log::error("❌ Failed to fetch price data for property ID: {$propertyId}");
            return;
        }

        $data = $response->json();

        if (!isset($data['prices'])) {
            Log::warning("⚠️ No prices found for property ID: {$propertyId}");
            return;
        }

        $prices = $data['prices'];

        // Save Price Configuration
        $config = PriceConfiguration::updateOrCreate(
            ['property_id' => $propertyId],
            [
                'account_id' => $accountId,
                'currency' => $prices['currency'] ?? null,
                'balance_due_days' => $prices['balanceDueDays'] ?? null,
                'deposit_type' => $prices['depositType'] ?? null,
                'deposit_value' => $prices['depositValue'] ?? null,
                'security_deposit' => $prices['securityDeposit'] ?? null,
            ]
        );

        // Save Fees
        PriceFee::where('price_configuration_id', $config->id)->delete();
        foreach ($prices['fees'] ?? [] as $fee) {
            PriceFee::create([
                'price_configuration_id' => $config->id,
                'name' => $fee['name'] ?? null,
                'type' => $fee['type'] ?? null,
                'price' => $fee['price'] ?? null,
                'vat' => $fee['vat'] ?? null,
                'refundable' => $fee['refundable'] ?? null,
                'hidden' => $fee['hidden'] ?? null,
                'fee_payable_with' => $fee['feePayableWith'] ?? null,
            ]);
        }

        // Save LOS Entries
        PriceLosEntry::where('price_configuration_id', $config->id)->delete();
        foreach ($prices['priceLos'] ?? [] as $entry) {
            PriceLosEntry::create([
                'price_configuration_id' => $config->id,
                'date' => $entry['date'] ?? null,
                'length_of_stay_prices' => json_encode($entry['lengthOfStayPrices'] ?? []),
            ]);
        }
    }
}
