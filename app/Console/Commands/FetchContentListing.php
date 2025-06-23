<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\ContentListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchContentListing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:content-listing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to fetch content listing from SuperControl API and update the database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        foreach (Property::all() as $property) {
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            ])->get('https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/' . $property->id);

            $data = $response->json();

            if (!isset($data['propertyId'])) {
                $this->warn("No content listing data found for property ID: {$property->property_id}");
                continue;
            }

            $configuration = $data['configuration'] ?? [];
            $acceptedPaymentForms = $configuration['acceptedPaymentForms'] ?? [];
            $acceptedPaymentFormsJson = json_encode($acceptedPaymentForms);

            ContentListing::updateOrCreate(
                ['property_id' => $data['propertyId']],
                [
                    'property_url' => $data['propertyUrl'] ?? null,
                    'customer_property_id' => $data['customerPropertyId'] ?? null,
                    'accepted_payment_forms' => $acceptedPaymentFormsJson,
                    'cancellation_policy' => $configuration['cancellationPolicy']['policy'] ?? null,
                    'check_in_time' => $configuration['checkInTime'] ?? null,
                    'check_out_time' => $configuration['checkOutTime'] ?? null,
                    'children_allowed' => $configuration['childrenAllowed'] ?? false,
                    'maximum_adults' => $configuration['maximumAdults'] ?? ($configuration['maximumOccupancy']['adults'] ?? 0),
                    'maximum_guests' => $configuration['maximumGuests'] ?? ($configuration['maximumOccupancy']['guests'] ?? 0),
                    'maximum_children' => $configuration['maximumChildren'] ?? ($configuration['maximumOccupancy']['children'] ?? 0),
                    'merchant_name' => $configuration['merchantName'] ?? null,
                    'pets_allowed' => $configuration['petsAllowed'] ?? false,
                    'smoking_allowed' => $configuration['smokingAllowed'] ?? false,
                    'allow_bookings' => $configuration['allowBookings'] ?? false,
                    'allow_enquiries' => $configuration['allowEnquiries'] ?? false,
                ]
            );
        }
    }
}
