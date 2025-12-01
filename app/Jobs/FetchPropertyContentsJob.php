<?php

namespace App\Jobs;

use App\Models\PropertyContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPropertyContentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Handle the job.
     */
    public function handle(): void
{
    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.hostaway.token'),
            'Cache-Control' => 'no-cache',
        ])->get("https://api.hostaway.com/v1/listings");

        if (!$response->successful()) {
            Log::error('Hostaway API request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return;
        }

        $properties = $response->json('result', []);

        if (empty($properties)) {
            Log::warning('No properties returned from Hostaway API.');
            return;
        }

        foreach ($properties as $propertyData) {

            $images = $propertyData['listingImages'] ?? [];
            $beds   = $propertyData['listingBedTypes'] ?? [];

            // Amenities (if coming)
            $listingAmenities = $propertyData['listingAmenities'] ?? [];
            $amenities = collect($listingAmenities)->pluck('amenityName')->toArray();

            // CREATE OR UPDATE PROPERTY CONTENT
            $content = PropertyContent::updateOrCreate(
                ['hostaway_id' => $propertyData['id']],
                [
                    'property_id'          => $propertyData['id'],
                    'customer_property_id' => $propertyData['id'],
                    'property_type'        => $propertyData['propertyTypeId'] ?? null,
                    'latitude'             => $propertyData['lat'] ?? null,
                    'longitude'            => $propertyData['lng'] ?? null,
                    'active'               => true,
                    'property_name'        => $propertyData['name'] ?? null,
                    'subcaption'           => $propertyData['externalListingName'] ?? null,
                    'description'          => $propertyData['description'] ?? null,
                    'address_line'         => $propertyData['street'] ?? null,
                    'city'                 => $propertyData['city'] ?? null,
                    'country'              => $propertyData['country'] ?? null,
                    'post_code'            => $propertyData['zipcode'] ?? null,
                    'bedroom_count'        => $propertyData['bedroomsNumber'] ?? 0,
                    'bathroom_count'       => $propertyData['bathroomsNumber'] ?? 0,
                    'area'                 => $propertyData['squareMeters'] ?? 0,
                    'area_unit'            => 'sqm',
                    'amenities'            => json_encode($amenities),
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | SAVE LISTING IMAGES
            |--------------------------------------------------------------------------
            */
            if (!empty($images)) {

                // Delete old images
                $content->images()->delete();

                foreach ($images as $img) {
                    $content->images()->create([
                        'photo_id' => $img['id'] ?? null,
                        'url'      => $img['url'] ?? null,
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | SAVE ROOM + BEDS
            |--------------------------------------------------------------------------
            */

            // Hostaway may have rooms structure in future
$roomsFromApi = $propertyData['rooms'] ?? [];  // if Hostaway sends rooms array

// Step 1: Delete old rooms & beds completely
$content->rooms()->each(function($room) {
    $room->beds()->delete();
});
$content->rooms()->delete();

// Step 2: Create rooms from Hostaway (if any)
if (!empty($roomsFromApi)) {

    foreach ($roomsFromApi as $r) {

        // Create Room
        $room = $content->rooms()->create([
            'name' => $r['name'] ?? 'Room',
            'type' => $r['type'] ?? null,
        ]);

        // Beds for this room
        if (!empty($r['beds'])) {
            foreach ($r['beds'] as $bed) {
                $room->beds()->create([
                    'bed_type' => $bed['bedTypeId'] ?? null,
                    'quantity' => $bed['quantity'] ?? 1,
                ]);
            }
        }
    }

} else {

    // Step 3: If Hostaway sends only listingBedTypes (not rooms)
    if (!empty($beds)) {

        // Create one room with beds
        $room = $content->rooms()->create([
            'name' => 'Bedroom',
            'type' => 'bedroom',
        ]);

        foreach ($beds as $bed) {
            $room->beds()->create([
                'bed_type' => $bed['bedTypeId'] ?? null,
                'quantity' => $bed['quantity'] ?? 1,
            ]);
        }
    }
}


            Log::info("Hostaway property updated: {$content->id}", [
                'name'           => $content->property_name,
                'images_count'   => count($images),
                'beds_count'     => count($beds),
                'amenities_count'=> count($amenities),
            ]);
        }

    } catch (\Exception $e) {
        Log::error('Exception while fetching Hostaway properties', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);
    }
}
}
