<?php

namespace App\Jobs;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchHostawayReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            $response = Http::withHeaders([
    'Authorization' => 'Bearer ' . env('HOSTAWAY_API_TOKEN'),
    'Accept' => 'application/json',
])->get('https://api.hostaway.com/v1/reviews');


            Log::info('Hostaway API status: '.$response->status());
            Log::info('Hostaway API body: '.$response->body());

            // Try to decode JSON safely
            $hostawayReviews = $response->json();

            if (!is_array($hostawayReviews)) {
                Log::error('Hostaway API did not return an array. Possibly invalid key or endpoint.');
                return; // exit safely
            }

            foreach ($hostawayReviews as $item) {
                // Check if $item is array
                if (!is_array($item)) {
                    Log::warning('Invalid item format, skipping.', ['item' => $item]);
                    continue;
                }

                Review::updateOrCreate(
                    [
                        'property_id'   => $item['listingMapId'] ?? null,
                        'reviewer_name' => $item['guestName'] ?? 'Anonymous',
                    ],
                    [
                        'review'         => $item['publicReview'] ?? $item['privateFeedback'] ?? '',
                        'location'       => null,
                        'arrival_date'   => $item['arrivalDate'] ?? null,
                        'departure_date' => $item['departureDate'] ?? null,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('FetchHostawayReviewsJob failed: '.$e->getMessage());
            throw $e;
        }
    }
}
