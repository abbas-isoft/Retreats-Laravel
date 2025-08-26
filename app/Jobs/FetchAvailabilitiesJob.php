<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\PropertyAvailability;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAvailabilitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    /**
     * Create a new job instance.
     */
    public function __construct(Account $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $response = Http::withHeader('SC-TOKEN', 'cddc35d0-1491-4b04-969c-9f8419ef0e99')
            ->get("https://api.supercontrol.co.uk/v3/properties/AvailabilityIndex/{$this->account->id}");

        $data = $response->json();

        if (!isset($data['properties']) || empty($data['properties'])) {
            Log::info("No availability data found for Account ID: {$this->account->id}");
            return;
        }

        foreach ($data['properties'] as $property) {
            PropertyAvailability::updateOrCreate(
                ['property_id' => $property['propertyId']],
                [
                    'account_id'   => $this->account->id,
                    'listing_url'  => $property['listingUrl'] ?? null,
                    'last_updated' => isset($property['lastUpdated'])
                        ? Carbon::parse($property['lastUpdated'])->toDateTimeString()
                        : now()->toDateTimeString(),
                ]
            );
        }

        Log::info("Availability data updated for Account ID: {$this->account->id}");
    }
}
