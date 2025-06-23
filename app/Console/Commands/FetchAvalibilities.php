<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAvalibilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:avalibilities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch property availabilities from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach (Account::all() as $account) {
            $response = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
                ->get('https://api.supercontrol.co.uk/v3/properties/AvailabilityIndex/'.$account->id);

            $data = $response->json();

            if (!isset($data['properties']) || empty($data['properties'])) {
                $this->info("No availability data found for Account ID: {$account->id}");
                Log::info("No availability data found for Account ID: {$account->id}");
                continue;
            }

            foreach ($data['properties'] as $property) {
                PropertyAvailability::updateOrCreate(
            ['property_id' => $property['propertyId']],
             [
                   'account_id' => $account->id,
                'listing_url' => $property['listingUrl'] ?? null,
                'last_updated' => isset($property['lastUpdated'])
                    ? Carbon::parse($property['lastUpdated'])->toDateTimeString()
                    : now()->toDateTimeString(),
            ]
            );
            }

            $this->info("Availability data updated for Account ID: {$account->id}");
        }
        
        $this->info("Fetch availability command completed.");
    }
}
