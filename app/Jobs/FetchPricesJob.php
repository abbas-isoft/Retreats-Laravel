<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Property;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use App\Models\PropertyPrice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

     public function handle(): void
    {
        foreach (Account::all() as $account) {
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/PricesIndex/'.$account->id);

            $data = $response->json();

            foreach ($data['properties'] as $property) {
                PropertyPrice::updateOrCreate(
                    ['property_id' => $property['propertyId']],
                    [
                        'account_id' => $account->id,
                        'last_updated' => Carbon::parse($property['lastUpdated'])->toDateTimeString(),
                        'listing_url' => $property['listingUrl'] ?? null,
                    ]
                );
            }
        }
    }
}