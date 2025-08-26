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

class FetchSinglePropertyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {

       foreach (Property::all() as $property) {
    $response = Http::withHeaders([
        'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
])->get('https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/' . $property->id);

    $data = $response->json();

    if (!isset($data['propertyId'])) {
        Log::warning("No data found for property ID: {$property->property_id}");
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