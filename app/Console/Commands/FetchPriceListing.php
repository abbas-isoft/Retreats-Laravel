<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use App\Models\Account;   // Use Account model to loop through accounts
use App\Models\Property;  // Use Property if needed
use Carbon\Carbon;
use Illuminate\Support\Arr;
class FetchPriceListing extends Command
{
    protected $signature = 'fetch:prices-Listing';
    protected $description = 'Fetch price configuration from API and update the database for all accounts and their properties';

    public function handle()
    {
        // Loop through all accounts
        foreach (Account::all() as $account) {
            // Get properties for the account - you can adjust this if you have relation defined
            $properties = Property::where('account_id', $account->id)->get();

            foreach ($properties as $property) {
                $this->fetchAndSavePrice($property->id, $account->id);
            }
        }

        $this->info('✅ Price configurations saved for all properties across all accounts.');
    }

    private function fetchAndSavePrice($propertyId, $accountId)
    {
        $response = Http::withHeaders([
            'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
        ])->get("https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}");
        

        if ($response->failed()) {
            $this->error("Failed to fetch price data for property ID: {$propertyId}");
            return;
        }

        $data = $response->json();
        $prices = $data['prices'];

        // Save Price Configuration with account_id
        $config = PriceConfiguration::updateOrCreate(
            ['property_id' => $propertyId],
            [
                'account_id' => $accountId,  // add account_id here
                'currency' => $prices['currency'],
                'deposit_type'     => Arr::get($prices, 'paymentSchedule.deposit.depositType'),
        'deposit_value'    => Arr::get($prices, 'paymentSchedule.deposit.value'),
        'security_deposit' => Arr::get($prices, 'paymentSchedule.securityDeposit.value'),
        'balance_due_days' => Arr::get($prices, 'paymentSchedule.balanceDueDays'),
            ]
        );

        // Save Fees
// PriceFee::where('price_configuration_id', $config->id)->delete();

foreach ($prices['fees'] ?? [] as $fee) {
    PriceFee::UpdateOrcreate([
        'price_configuration_id' => $config->id,
        'name' => $fee['name'],
        'type' => $fee['type'],
        'price' => $fee['price'],
        'vat' => $fee['vat'],
        'refundable' => $fee['refundable'],
        'hidden' => $fee['hidden'],
        'fee_payable_with' => $fee['feePayableWith'],
    ]);
}


        // Save LOS Entries
        PriceLosEntry::where('price_configuration_id', $config->id)->delete();
        foreach ($prices['priceLos'] ?? [] as $entry) {
            $parts = explode(',', $entry);
            $date = $parts[0];
            PriceLosEntry::create([
                'price_configuration_id' => $config->id,
                'date' => $date,
                'length_of_stay_prices' => json_encode($prices),
            ]);
        }
    }
}
