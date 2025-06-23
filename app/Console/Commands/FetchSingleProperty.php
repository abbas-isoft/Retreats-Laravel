<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchSingleProperty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:single-property';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = ' Command to fetch a single property from SuperControl API and update the database';

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
        $this->warn("No data found for property ID: {$property->property_id}");
        continue;
    }

    $configuration = $data['configuration'] ?? [];
    $cardForms = $configuration['acceptedPaymentForms']['cardPaymentForms'] ?? [];
    $cardFormsJson = json_encode($cardForms);

    \App\Models\SingleConfiguration::updateOrCreate(
        ['property_id' => $data['propertyId']],
        [
            'property_url' => $data['propertyUrl'] ?? null,
            'customer_property_id' => $data['customerPropertyId'] ?? null,
            'card_payment_forms' => $cardFormsJson,
            'cancellation_policy' => $configuration['cancellationPolicy']['policy'] ?? null,
            'check_in_time' => $configuration['checkInTime'] ?? null,
            'check_out_time' => $configuration['checkOutTime'] ?? null,
            'children_allowed' => $configuration['childrenAllowed'] ?? null,
            'max_adults' => $configuration['maximumOccupancy']['adults'] ?? null,
            'max_guests' => $configuration['maximumOccupancy']['guests'] ?? null,
            'max_children' => $configuration['maximumOccupancy']['children'] ?? null,
            'merchant_name' => $configuration['merchantName'] ?? null,
            'card_code' => $cardForms[0]['cardCode'] ?? null,
            'card_type' => $cardForms[0]['cardType'] ?? null,
            'pets_allowed' => $configuration['petsAllowed'] ?? null,
            'smoking_allowed' => $configuration['smokingAllowed'] ?? null,
            'allow_bookings' => $configuration['allowBookings'] ?? null,
            'allow_enquiries' => $configuration['allowEnquiries'] ?? null,
        ]
    );
}

    }
}
