<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PropertyPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch property prices from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
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
