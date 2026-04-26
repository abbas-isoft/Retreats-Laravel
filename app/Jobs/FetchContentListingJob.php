<?php

namespace App\Jobs;

use App\Models\PropertyContent;
use App\Models\ContentListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchContentListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Loop through all PropertyContent
        foreach (PropertyContent::all() as $propertyContent) {

            // Use hostaway_id if present and not empty, otherwise fallback to property_id
            $hostawayId = !empty($propertyContent->hostaway_id) 
                ? $propertyContent->hostaway_id 
                : $propertyContent->property_id;

            if (!$hostawayId) {
                Log::warning("Both Hostaway ID and Property ID missing for PropertyContent ID: {$propertyContent->id}");
                continue;
            }

            // Fetch listing data from Hostaway API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Cache-Control' => 'no-cache',
            ])->get("https://api.hostaway.com/v1/listings/" . $hostawayId);

            $data = $response->json();

            if (!isset($data['result']) || !$data['result']) {
                Log::warning("Hostaway: No result found for ID: {$hostawayId}", [
                    'response' => $data
                ]);
                continue;
            }

            $listing = $data['result'];

            // Update or create ContentListing
            ContentListing::updateOrCreate(
                ['property_id' => $propertyContent->property_id], // match by property_id
                [
                    'property_url'          => $listing['publicAddress'] ?? null,
                    'customer_property_id'  => $listing['internalListingName'] ?? 'N/A',
                    'accepted_payment_forms' => $listing['acceptedPaymentForms'] ?? '[]', // default empty JSON array
                    'cancellation_policy'   => $listing['cancellationPolicy'] ?? 'standard',
                    'check_in_time'         => $listing['checkInTimeStart'] ?? '16:00:00',
                    'check_out_time'        => $listing['checkOutTime'] ?? '11:00:00',
                    'children_allowed'      => true,
                    'maximum_adults'        => $listing['personCapacity'] ?? 0,
                    'maximum_guests'        => $listing['personCapacity'] ?? 0,
                    'maximum_children'      => $listing['maxChildrenAllowed'] ?? 0,
                    'maximum_infants'       => $listing['maxInfantsAllowed'] ?? 0,
                    'maximum_pets'          => $listing['maxPetsAllowed'] ?? 0,
                    'merchant_name'         => $listing['merchantName'] ?? 'N/A',
                    'pets_allowed'          => ($listing['maxPetsAllowed'] ?? 0) > 0,
                    'smoking_allowed'       => $listing['smokingAllowed'] ?? false,
                    'allow_bookings'        => $listing['instantBookable'] ?? false,
                    'allow_enquiries'       => true,
                ]
            );

            Log::info("ContentListing updated for PropertyContent ID: {$propertyContent->id}, using ID: {$hostawayId}");
        }
    }
}
