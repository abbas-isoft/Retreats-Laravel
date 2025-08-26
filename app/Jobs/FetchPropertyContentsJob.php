<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Property;
use App\Models\PriceConfiguration;
use App\Models\PriceFee;
use App\Models\PriceLosEntry;
use App\Models\PropertyPrice;
use App\Models\PropertyContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FetchPropertyContentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

     public function handle(): void
    {
        foreach (\App\Models\Property::all() as $property) {
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/Properties/Listing/' . $property->id);

            $data = $response->json();

            if (!isset($data['propertyId'])) {
                Log::warning("No valid property data for property ID: {$property->id}");
                continue;
            }

            $ad = $data['adContent'] ?? [];
            $location = $data['location'] ?? [];
            $address = $location['address'] ?? [];
            $geoCode = $location['geoCode'] ?? [];
            $propertyDetails = $data['property'] ?? [];
            $images = $data['images'] ?? [];
            $rooms = $propertyDetails['rooms'] ?? [];
          

            // Update or create main property content
            $propertyContent = PropertyContent::updateOrCreate(
                ['property_id' => $data['propertyId']],
                [
                    'customer_property_id'    => $data['customerPropertyId'] ?? null,
                    'active'                  => $data['active'] ?? false,
                    'property_name'           => $ad['propertyName'] ?? null,
                    'subcaption'              => $ad['subcaption'] ?? null,
                    'description'             => $ad['description'] ?? null,
                    'accommodations_summary'  => $ad['accommodationsSummary'] ?? null,
                    'location_description'    => isset($ad['location']) ? strip_tags($ad['location']) : null,
                    'changeover'              => $ad['changeover'] ?? null,
                    'second'                  => $ad['second'] ?? null,

                    'address_line'            => $address['addressLine'] ?? null,
                    'city'                    => $address['city'] ?? null,
                    'country'                 => $address['country'] ?? null,
                    'post_code'               => $address['postCode'] ?? null,

                    'latitude'                => $geoCode['latitude'] ?? null,
                    'longitude'               => $geoCode['longitude'] ?? null,
                    'property_type'           => $propertyDetails['propertyType'] ?? null,
                    'registration_number'     => $propertyDetails['registrationNumber'] ?? null,
                    'epcRating'               => $propertyDetails['epcRating'] ?? null,

                    'bedroom_count'           => is_array($propertyDetails['bedrooms'] ?? null) ? count($propertyDetails['bedrooms']) : 0,
                    'bathroom_count'          => is_array($propertyDetails['bathrooms'] ?? null) ? count($propertyDetails['bathrooms']) : 0,
                    'area'                    => $propertyDetails['area'] ?? 0,
                    'area_unit'               => $propertyDetails['areaUnit'] ?? null,
                    'amenities'               => json_encode($propertyDetails['amenities'] ?? []),
                ]
            );

            // Handle images: clear old images and insert fresh ones
            $propertyContent->images()->delete(); // delete old images

            foreach ($images as $image) {
                $propertyContent->images()->create([
                    'photo_id' => $image['photoId'] ?? null,
                    'url' => $image['url'] ?? null,
                ]);
            }

            // Handle rooms and beds
            $propertyContent->rooms()->delete(); // delete old rooms (cascade should delete beds if set)

            foreach ($rooms as $roomData) {
                $room = $propertyContent->rooms()->create([
                    'name' => $roomData['name'] ?? null,
                ]);

                $beds = $roomData['beds'] ?? [];
                foreach ($beds as $bedData) {
                    $room->beds()->create([
                        'bed_type' => $bedData['bedType'] ?? null,
                        'quantity' => $bedData['quantity'] ?? 0,
                    ]);
                }
            }
        }

        Log::info("✅ Property content fetch complete!");
    }
}