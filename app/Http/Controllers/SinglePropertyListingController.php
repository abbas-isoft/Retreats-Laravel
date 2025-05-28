<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\SingleConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class SinglePropertyListingController extends Controller
{
    public function index($id)
    {
        $property = Property::find($id);
        return view('single-property-listing', [
            'configurations' => SingleConfiguration::where('property_id', $property->propertyId)->latest()->get(),
            'property' => $property,
        ]);
    }


   public function fetch($id)
{
    try {
        
        $propertyId = Property::find($id)->propertyId;

        $response = Http::withHeaders([
            'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            'Accept' => 'application/json',
        ])->get("https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/{$propertyId}?propertyLastUpdated=");

        if ($response->failed()) {
            return redirect()->back()->with('error', 'API request failed.');
        }

        $data = $response->json();

        // dd($data);
        // Save or update the data
        SingleConfiguration::updateOrCreate(
            ['property_id' => $propertyId],
            [
                'property_url' => $data['propertyUrl'] ?? null,
                'customer_property_id' => $data['customerPropertyId'] ?? null,
                'card_payment_forms' => $data['configuration']['acceptedPaymentForms']['cardPaymentForms']['cardCode'] ?? null,
                'cancellation_policy' => $data ['configuration']['cancellationPolicy']['policy'] ?? null,
                'occupancy' => json_encode($data['occupancy'] ?? []),
                'merchant_name' => $data['configuration']['merchantName'] ?? null,
                'pets_allowed' => $data['configuration']['petsAllowed'] ?? null,
                'smoking_allowed' => $data['configuration']['smokingAllowed'] ?? null,
                'children_allowed' => $data['configuration']['childrenAllowed'] ?? null,
                'allow_bookings' => $data['configuration']['allowBookings'] ?? null,
                'allow_enquiries' => $data['configuration']['allowEnquiries'] ?? null,
                'check_in_time' => $data['configuration']['checkInTime'] ?? null,
                'check_out_time' => $data['configuration']['checkOutTime'] ?? null,
                'card_codes' => $data['configuration']['acceptedPaymentForms']['cardPaymentForms']['cardTypes'] ?? null,
                'max_adults' => $data['configuration']['maximumOccupancy']['adults'] ?? null,
                'max_guests' => $data['configuration']['maximumOccupancy']['guests'] ?? null,
                'max_children' => $data['configuration']['maximumOccupancy']['children'] ?? null,
            ]
        );

        return redirect()->route('single.property.listing', $id)
            ->with('success', 'Single Property Configuration fetched successfully from API.');

    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
    }
}
}
