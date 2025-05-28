<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
class PropertyContentController extends Controller
{
    public function index($id): View
    {
        $property = Property::findOrFail($id);

        $contents = PropertyContent::where('property_id', $property->propertyId)
            ->with(['images', 'rooms.beds'])
            ->latest()
            ->get();

        return view('property-content', [
            'contents' => $contents,
            'propertyId' => $property->id,
            'accountId' => $property->account_id,
        ]);
    }

    // Fetch live data from API and update DB
    public function fetchAndUpdate($id): RedirectResponse
    {
        try {
            $property = Property::findOrFail($id);
            $propertyId = $property->propertyId;


            // API call to get property content by propertyId
            $response = Http::withHeaders([
                'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept' => 'application/json',
            ])->get("https://api.supercontrol.co.uk/v3/Properties/Listing/{$propertyId}");

            $data = $response->json();

            if (!$data) {
                return redirect()->back()->with('error', 'No data received from API.');
            }

            // Update or create PropertyContent

            $propertyContent = PropertyContent::updateOrCreate(
                ['property_id' => $propertyId],
                [
                    'customer_property_id' => $data['customerPropertyId'] ?? null,
                    'active' => $data['active'] ?? false,
                    'property_name' => $data['adContent']['propertyName'] ?? null,
                    'subcaption' => $data['adContent']['subcaption'] ?? null,
                    'description' => $data['adContent']['description'] ?? null,
                    'accommodations_summary' => $data['adContent']['accommodationsSummary'] ?? null,
                    'location_description' => $data['adContent']['locationDescription'] ?? null,
                    'changeover' => $data['adContent']['changeover'] ?? null,
                    'second' => $data['adContent']['second'] ?? null,
                    'address_line' => $data['location']['address']['addressLine'] ?? null,
                    'city' => $data['location']['address']['city'] ?? null,
                    'country' => $data['location']['address']['country'] ?? null,
                    'post_code' => $data['location']['address']['postCode'] ?? null,
                    'latitude' => $data['location']['geoCode']['latitude'] ?? null,
                    'longitude' => $data['location']['geoCode']['longitude'] ?? null,
                    'property_type' => $data['property']['propertyType'] ?? null,
                    'registration_number' => $data['property']['registrationNumber'] ?? null,
                    'epcRating' => $data['property']['epcRating'] ?? null,
                    'bedroom_count' => count($data['property']['bedrooms']),
                    'bathroom_count' => count($data['property']['bathrooms']),
                    'area' => $data['property']['area'] ?? null,
                    'area_unit' => $data['property']['areaUnit'] ?? null,
                    'amenities' => $data['property']['amenities'] ?? [],
                ]
            );

            // Sync Images (delete old and add new ones)
            if (isset($data['images']) && is_array($data['images'])) {
                // Delete old images first
                $propertyContent->images()->delete();

                foreach ($data['images'] as $image) {
                    $propertyContent->images()->create([
                        'photo_id' => $image['photoId'] ?? null,
                        'url' => $image['url'] ?? null,
                    ]);
                }
            }

            // // Sync Rooms and Beds (delete old and add new ones)
            if (isset($data['property']['rooms']) && is_array($data['property']['rooms'])) {
                // delete old rooms (cascade deletes beds too)
                $propertyContent->rooms()->delete();
                foreach ($data['property']['rooms'] as $room) {
                    $newRoom = $propertyContent->rooms()->create([
                        'name' => $room['name'] ?? null,
                    ]);

                    if (isset($room['beds']) && is_array($room['beds'])) {
                        foreach ($room['beds'] as $bed) {
                            $newRoom->beds()->create([
                                'bed_type' => $bed['bedType'] ?? null,
                                'quantity' => $bed['quantity'] ?? 0,
                            ]);
                        }
                    }
                }
            }



            //     // ✅ ROOMS & BEDS
            // if (!empty($data['rooms'])) {
            //     $propertyContent->rooms()->delete();

            //     foreach ($data['rooms'] as $room) {
            //         $newRoom = $propertyContent->rooms()->create([
            //             'name' => $room['name'] ?? null,
            //         ]);

            //         if (!empty($room['beds'])) {
            //             foreach ($room['beds'] as $bed) {
            //                 $newRoom->beds()->create([
            //                     'bed_type' => $bed['bedType'] ?? null,
            //                     'quantity' => $bed['quantity'] ?? 0,
            //                 ]);
            //             }
            //         }
            //     }
            // }

            return redirect()->route('property-content.index', $id)
                ->with('success', 'Property content updated successfully from live API.');

        } catch (\Exception $e) {
            Log::error('Property content fetch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Fetch failed: ' . $e->getMessage());
        }
    }
}
