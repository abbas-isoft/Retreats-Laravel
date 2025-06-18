<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchPropertyJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private Account $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $properties = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
            ->get('https://api.supercontrol.co.uk/v3/properties/ContentIndex/'.$this->account->accountId)
            ->json();

        foreach ($properties['properties'] as $property) {
            Property::updateOrCreate(
                ['propertyId' => $property['propertyId']],
                [
                    'account_id' => $this->account->id,
                    'active' => $property['active'],
                    'lastUpdated' => Carbon::parse($property['lastUpdated'])->toDateTimeString(),
                    'listingUrl' => $property['listingUrl'],
                ]
            );
        }
    }
}
