<?php

namespace App\Http\Controllers;

use App\Models\ContentListing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentListingController extends Controller
{
    public function index()
    {
        return view('content-listing', [
            'contents' => ContentListing::all()
        ]);
    }

    // Bulk update all contents by fetching data from API
    public function fetchAndUpdate()
    {
        try {
            
            $contents = ContentListing::all();

            foreach ($contents as $content) {
                $propertyId = $content->property_id;
                $url = "https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/{$propertyId}?propertyLastUpdated=2021-04-01T00:00:00Z";

                $response = Http::withHeaders([
                    'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                    'Accept' => 'application/json',
                ])->get($url);

                if ($response->failed()) {
                    Log::error("API call failed for property ID: {$propertyId}");
                    continue; // Skip to next content if API fails for one
                }

                $data = $response->json();

                $content->update([
                    'customer_property_id' => $data['customerPropertyId'] ?? null,
                    'property_url' => $data['propertyUrl'] ?? null,
                    'merchant_name' => $data['merchantName'] ?? null,
                    'maximum_adults' => $data['maximumAdults'] ?? 0,
                    'maximum_children' => $data['maximumChildren'] ?? 0,
                    'maximum_guests' => $data['maximumGuests'] ?? 0,
                    'check_in_time' => $data['checkInTime'] ?? null,
                    'check_out_time' => $data['checkOutTime'] ?? null,
                    'children_allowed' => $data['childrenAllowed'] ?? false,
                    'pets_allowed' => $data['petsAllowed'] ?? false,
                    'smoking_allowed' => $data['smokingAllowed'] ?? false,
                    'updated_at' => Carbon::now(),
                ]);
            }

            return redirect()->back()->with('success', 'All content listings updated successfully!');
        } catch (\Exception $e) {
            Log::error('Bulk content update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Bulk update failed: ' . $e->getMessage());
        }
    }

    // Update a single content by ID via AJAX call
    public function update($id)
    {
        try {
            $content = ContentListing::findOrFail($id);
            $propertyId = $content->property_id;

            $url = "https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/{$propertyId}?propertyLastUpdated=2021-04-01T00:00:00Z";

            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept' => 'application/json',
            ])->get($url);

            if ($response->failed()) {
                Log::error("API call failed for property ID: {$propertyId}");
                return response()->json(['message' => 'Failed to fetch data from API.'], 500);
            }

            $data = $response->json();

            $content->update([
                'customer_property_id' => $data['customerPropertyId'] ?? null,
                'property_url' => $data['propertyUrl'] ?? null,
                'merchant_name' => $data['merchantName'] ?? null,
                'maximum_adults' => $data['maximumAdults'] ?? 0,
                'maximum_children' => $data['maximumChildren'] ?? 0,
                'maximum_guests' => $data['maximumGuests'] ?? 0,
                'check_in_time' => $data['checkInTime'] ?? null,
                'check_out_time' => $data['checkOutTime'] ?? null,
                'children_allowed' => $data['childrenAllowed'] ?? false,
                'pets_allowed' => $data['petsAllowed'] ?? false,
                'smoking_allowed' => $data['smokingAllowed'] ?? false,
                'updated_at' => Carbon::now(),
            ]);

            return response()->json([
                'message' => 'Content listing updated successfully!',
                'updated_at' => $content->updated_at->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Content update failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
