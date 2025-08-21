<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\AvailabilityCalendar;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAvailabilityCalendarsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set job timeout and retry
    public $timeout = 300;       // 5 minutes
    public $retryAfter = 300;    // Retry after 5 minutes if failed

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 FetchAvailabilityCalendarsJob started.');

        // Process properties in chunks to avoid memory issues
        Property::chunk(50, function ($properties) {
            foreach ($properties as $property) {
                $propertyId = $property->id;

                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                ])->timeout(60)->retry(3, 1000) // 3 retries with 1 sec interval
                  ->get("https://api.supercontrol.co.uk/v3/Properties/Availability/{$propertyId}");

                if ($response->failed()) {
                    Log::error("❌ Failed to fetch availability for Property ID: {$propertyId}");
                    continue;
                }

                $data = $response->json();

                if (!isset($data['propertyAvailability'])) {
                    Log::info("ℹ️ No availability data for Property ID: {$propertyId}");
                    continue;
                }

                $availability = $data['propertyAvailability'];

                AvailabilityCalendar::updateOrCreate(
                    ['property_id' => $propertyId],
                    [
                        'start_date' => substr($availability['startDate'], 0, 10),
                        'end_date' => substr($availability['endDate'], 0, 10),
                        'availability' => $availability['availability'],
                        'change_over' => $availability['changeOver'],
                        'max_stay' => $availability['maxStay'],
                        'min_prior_notify' => $availability['minPriorNotify'],
                        'min_stay' => $availability['minStay'],
                    ]
                );

                Log::info("✅ Updated availability for Property ID: {$propertyId}");
            }
        });

        Log::info('✅ FetchAvailabilityCalendarsJob completed.');
    }
}
