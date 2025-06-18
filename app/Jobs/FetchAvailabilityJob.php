<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchAvailabilityJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private Account $account)
    {
        // $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $availabilites = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
            ->get('https://api.supercontrol.co.uk/v3/properties/AvailabilityIndex/'.$this->account->accountId)
            ->json();

        foreach ($availabilites['properties'] as $property) {
            PropertyAvailability::updateOrCreate(
                ['property_id' => $property['propertyId']],
                [
                    'account_id' => $this->account->id,
                    'listing_url' => $property['listingUrl'],
                    'last_updated' => Carbon::parse($property['lastUpdated'])->toDateTimeString(),
                ]
            );
        }
    }
}
