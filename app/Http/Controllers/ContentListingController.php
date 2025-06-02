<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\ContentListing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ContentListingController extends Controller
{
    public function index($id)
    {
        $property = Property::find($id);
        

        return view('content-listing', [
    'contents' => ContentListing::where('property_id', $property->propertyId)->latest()->get(),
    'propertyId' => $id,
    'accountId' => $property->account_id, // ← this line fixes the error
]);
    }

    public function fetchAndUpdate($id)
    {
        try {
            $propertyId = Property::find($id)->propertyId;

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept' => 'application/json',
            ])->get("https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/{$propertyId}?propertyLastUpdated=2021-04-01T00:00:00Z");

            $data = $response->json();
            $config = $data['configuration'] ?? [];

            ContentListing::updateOrCreate(
                ['property_id' => $propertyId],
                [
                    'customer_property_id' => $data['customerPropertyId'] ?? null,
                    'merchant_name' => $config['merchantName'] ?? null,
                    'maximum_adults' => $config['maximumOccupancy']['adults'] ?? 0,
                    'maximum_children' => $config['maximumOccupancy']['children'] ?? 0,
                    'maximum_guests' => $config['maximumOccupancy']['guests'] ?? 0,
                    'check_in_time' => $config['checkInTime'] ?? null,
                    'check_out_time' => $config['checkOutTime'] ?? null,
                    'children_allowed' => $config['childrenAllowed'] ?? false,
                    'pets_allowed' => $config['petsAllowed'] ?? false,
                    'smoking_allowed' => $config['smokingAllowed'] ?? false,
                    'allow_bookings' => $config['allowBookings'] ?? false,
                    'allow_enquiries' => $config['allowEnquiries'] ?? false,
                    'accepted_payment_forms' => $config['acceptedPaymentForms']['cardPaymentForms'] ?? [],
                    'cancellation_policy' => $config['cancellationPolicy']['policy'] ?? null,
                    'updated_at' => now(),
                ]
            );

            return redirect()->route('content.index', $id)->with('success', 'All content listings updated from live API.');

        } catch (\Exception $e) {
            Log::error('Content fetch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Fetch failed: ' . $e->getMessage());
        }
    }
}
