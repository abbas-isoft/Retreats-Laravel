<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ContentListing;
use App\Models\Listing;
use App\Models\PriceConfiguration;
use App\Models\Property;
use App\Models\PropertyAvailability;
use App\Models\PropertyConfiguration;
use App\Models\PropertyPrice;
use App\Models\SingleConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SuperControlController extends Controller
{
    public function fetchAccounts()
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.supercontrol.co.uk/v3/properties/index',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['accounts']) && is_array($data['accounts'])) {
            foreach ($data['accounts'] as $item) {
                // Save or update account
                $account = Account::updateOrCreate(
                    ['accountId' => $item['accountId']],
                    [
                        'companyName' => $item['companyName'] ?? null,
                        'contentIndexUrl' => $item['contentIndexUrl'] ?? null,
                        'configurationIndexUrl' => $item['configurationIndexUrl'] ?? null,
                        'pricesIndexUrl' => $item['pricesIndexUrl'] ?? null,
                        'availabilityIndexUrl' => $item['availabilityIndexUrl'] ?? null,
                        'updated_at' => now(),
                    ]
                );

                // ✅ Now also fetch and update prices for this account
                $this->fetchAccountPrices($item['accountId']);
            }

            return response()->json(['message' => 'Accounts and prices fetched successfully']);
        }

        return response()->json(['message' => 'No accounts found from API', 'response' => $data]);
    }

    public function fetchAccountProperties($account_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.supercontrol.co.uk/v3/properties/index',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['accounts']) && is_array($data['accounts'])) {
            foreach ($data['accounts'] as $accountItem) {
                // Find local account ID
                $accountDbId = Account::where('accountId', $accountItem['accountId'])->value('id');

                if (! $accountDbId) {
                    continue;
                }

                $contentIndexUrl = "https://api.supercontrol.co.uk/v3/properties/ContentIndex/{$accountItem['accountId']}";
                $propertyCurl = curl_init();

                curl_setopt_array($propertyCurl, [
                    CURLOPT_URL => $contentIndexUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                        'Accept: application/json',
                    ],
                ]);

                $propertyResponse = curl_exec($propertyCurl);
                curl_close($propertyCurl);

                $propertyData = json_decode($propertyResponse, true);

                if (isset($propertyData['properties'])) {
                    foreach ($propertyData['properties'] as $property) {
                        $lastUpdated = isset($property['lastUpdated']) ? Carbon::parse($property['lastUpdated'])->toDateTimeString() : now();

                        Property::updateOrCreate(
                            ['propertyId' => $property['propertyId']],
                            [
                                'account_id' => $accountDbId,
                                'active' => $property['active'] ?? null,
                                'lastUpdated' => $lastUpdated,
                                'listingUrl' => $property['listingUrl'] ?? null,
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }

            return response()->json(['message' => 'All properties fetched and saved successfully.']);
        }

        return response()->json(['message' => 'No accounts found or invalid API response.', 'response' => $data]);
    }

    public function fetchAccountConfigurations($accountId)
    {
        $accountDbId = Account::where('accountId', $accountId)->value('id');

        if (! $accountDbId) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.supercontrol.co.uk/v3/Properties/ConfigurationIndex/{$accountId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['configurationDefaults']) && isset($data['properties']) && is_array($data['properties'])) {
            $default = $data['configurationDefaults'];
            $propertyIdsFromApi = [];

            foreach ($data['properties'] as $item) {
                $propertyId = $item['propertyId'];
                $propertyIdsFromApi[] = $propertyId;

                $lastUpdated = isset($item['lastUpdated']) ? Carbon::parse($item['lastUpdated'])->toDateTimeString() : now();

                if (isset($default['acceptedPaymentForms']['cardPaymentForms'])) {
                    foreach ($default['acceptedPaymentForms']['cardPaymentForms'] as $card) {
                        PropertyConfiguration::updateOrCreate(
                            [
                                'property_id' => $propertyId,
                                'account_id' => $accountDbId,
                                'card_code' => $card['cardCode'],
                            ],
                            [
                                'card_type' => $card['cardType'],
                                'cancellation_policy' => $default['cancellationPolicy']['policy'] ?? null,
                                'merchant_name' => $default['merchantName'] ?? null,
                                'minimum_age' => $default['minimumAge'] ?? null,
                                'rental_agreement_url' => $default['rentalAgreement']['rentalAgreementPdfUrl'] ?? null,
                                'last_updated' => $lastUpdated,
                                'configurationContentUrl' => $item['configurationContentUrl'],
                            ]
                        );
                    }
                }
            }

            // Delete local configurations for this account's properties not present in API response
            $localPropertyIds = Property::where('account_id', $accountDbId)->pluck('propertyId')->toArray();

            $deletedPropertyIds = array_diff($localPropertyIds, $propertyIdsFromApi);

            if (! empty($deletedPropertyIds)) {
                PropertyConfiguration::whereIn('property_id', $deletedPropertyIds)->delete();
            }

            return response()->json([
                'message' => 'Configuration synced successfully',
                'data' => PropertyConfiguration::whereIn('property_id', $propertyIdsFromApi)->get(),
            ]);
        }

        return response()->json(['message' => 'No configuration data found or invalid API response', 'response' => $data]);
    }

    public function fetchAccountPrices($accountId)
    {
        $accountDbId = Account::where('accountId', $accountId)->value('id');

        if (! $accountDbId) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.supercontrol.co.uk/v3/Properties/PricesIndex/{$accountId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (isset($data['properties']) && is_array($data['properties'])) {
            foreach ($data['properties'] as $item) {
                $lastUpdated = isset($item['lastUpdated']) ? Carbon::parse($item['lastUpdated'])->toDateTimeString() : null;

                PropertyPrice::updateOrCreate(
                    ['property_id' => $item['propertyId']],
                    [
                        'account_id' => $accountDbId,
                        'last_updated' => $lastUpdated,
                        'listing_url' => $item['listingUrl'],
                    ]
                );

            }

            return response()->json([
                'message' => 'Prices updated from live API successfully.',
                'data' => PropertyPrice::where('account_id', $accountDbId)->get(),
            ]);
        }

        return response()->json(['message' => 'No prices data found from API.']);
    }

    // public function fetchAccountAvailability($accountId)
    // {
    //     $accountDbId = Account::where('accountId', $accountId)->value('id');

    //     if (! $accountDbId) {
    //         return response()->json(['message' => 'Account not found'], 404);
    //     }

    //     $curl = curl_init();
    //     curl_setopt_array($curl, [
    //         CURLOPT_URL => "https://api.supercontrol.co.uk/v3/Properties/AvailabilityIndex/{$accountId}",
    //         CURLOPT_RETURNTRANSFER => true,
    //         CURLOPT_HTTPHEADER => [
    //             'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
    //             'Accept: application/json',
    //         ],
    //     ]);

    //     $response = curl_exec($curl);
    //     curl_close($curl);

    //     $data = json_decode($response, true);

    //     if (isset($data['properties'])) {
    //         foreach ($data['properties'] as $item) {
    //             $lastUpdated = Carbon::parse($item['lastUpdated'])->toDateTimeString();

    //             PropertyAvailability::updateOrCreate(
    //                 ['property_id' => $item['propertyId']],
    //                 [
    //                     'account_id' => $accountDbId,
    //                     'listing_url' => $item['listingUrl'],
    //                     'last_updated' => $lastUpdated,
    //                 ]
    //             );
    //         }

    //         return response()->json([
    //             'message' => 'Availability listings saved successfully',
    //             'data' => PropertyAvailability::where('account_id', $accountDbId)->get(),
    //         ]);
    //     }

    //     return response()->json(['message' => 'No availability data found']);
    // }


//     public function fetchAccountAvailability($accountDbId)
// {
//     // Step 1: Get the accountId from database using internal DB id
//     $account = Account::find($accountDbId);

//     if (! $account) {
//         return response()->json(['message' => 'Account not found'], 404);
//     }

//     $apiAccountId = $account->accountId;

//     // Step 2: Call the API using accountId
//     $curl = curl_init();
//     curl_setopt_array($curl, [
//         CURLOPT_URL => "https://api.supercontrol.co.uk/v3/Properties/AvailabilityIndex/{$apiAccountId}",
//         CURLOPT_RETURNTRANSFER => true,
//         CURLOPT_HTTPHEADER => [
//             'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
//             'Accept: application/json',
//         ],
//     ]);

//     $response = curl_exec($curl);
//     curl_close($curl);

//     $data = json_decode($response, true);

//     if (isset($data['properties'])) {
//         foreach ($data['properties'] as $item) {
//             $lastUpdated = \Carbon\Carbon::parse($item['lastUpdated'])->toDateTimeString();

//             \App\Models\PropertyAvailability::updateOrCreate(
//                 ['property_id' => $item['propertyId']],
//                 [
//                     'account_id'   => $accountDbId,
//                     'listing_url'  => $item['listingUrl'],
//                     'last_updated' => $lastUpdated,
//                 ]
//             );
//         }

//         return response()->json([
//             'message' => 'Availability listings saved successfully',
//             'data'    => \App\Models\PropertyAvailability::where('account_id', $accountDbId)->get(),
//         ]);
//     }

//     return response()->json(['message' => 'No availability data found']);
// }


    public function fetchListing($accountId)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.supercontrol.co.uk/v3/properties/ListingIndex/{$accountId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $data = json_decode($response, true);

        if (! isset($data['properties']) || empty($data['properties'])) {
            return response()->json([
                'message' => 'No properties found in listing',
                'debug' => $data,
            ]);
        }

        foreach ($data['properties'] as $property) {
            $conf = $property['configuration'];

            Listing::updateOrCreate(
                ['property_id' => $property['propertyId']],
                [
                    'property_url' => $property['propertyUrl'] ?? null,
                    'customer_property_id' => $property['customerPropertyId'] ?? null,
                    'card_payment_forms' => $conf['acceptedPaymentForms']['cardPaymentForms'] ?? [],
                    'cancellation_policy' => $conf['cancellationPolicy']['policy'] ?? null,
                    'check_in_time' => $conf['checkInTime'] ?? null,
                    'check_out_time' => $conf['checkOutTime'] ?? null,
                    'children_allowed' => $conf['childrenAllowed'] ?? false,
                    'max_adults' => $conf['maximumOccupancy']['adults'] ?? null,
                    'max_guests' => $conf['maximumOccupancy']['guests'] ?? null,
                    'max_children' => $conf['maximumOccupancy']['children'] ?? null,
                    'merchant_name' => $conf['merchantName'] ?? null,
                    'pets_allowed' => $conf['petsAllowed'] ?? false,
                    'smoking_allowed' => $conf['smokingAllowed'] ?? false,
                    'allow_bookings' => $conf['allowBookings'] ?? true,
                    'allow_enquiries' => $conf['allowEnquiries'] ?? true,
                ]
            );
        }

        return response()->json(['message' => 'Listing data saved successfully', 'data' => Listing::all()]);
    }

    // public function saveSingleConfiguration(Request $request)
    // {
    //     $data = $request->all();
    //     $conf = $data['configuration'] ?? [];

    //     $record = SingleConfiguration::updateOrCreate(
    //         ['property_id' => $data['propertyId']],
    //         [
    //             'property_url' => $data['propertyUrl'] ?? null,
    //             'customer_property_id' => $data['customerPropertyId'] ?? null,
    //             'card_payment_forms' => $conf['acceptedPaymentForms']['cardPaymentForms'] ?? [],
    //             'cancellation_policy' => $conf['cancellationPolicy']['policy'] ?? null,
    //             'check_in_time' => $conf['checkInTime'] ?? null,
    //             'check_out_time' => $conf['checkOutTime'] ?? null,
    //             'children_allowed' => $conf['childrenAllowed'] ?? false,
    //             'max_adults' => $conf['maximumOccupancy']['adults'] ?? null,
    //             'max_guests' => $conf['maximumOccupancy']['guests'] ?? null,
    //             'max_children' => $conf['maximumOccupancy']['children'] ?? null,
    //             'merchant_name' => $conf['merchantName'] ?? null,
    //             'pets_allowed' => $conf['petsAllowed'] ?? false,
    //             'smoking_allowed' => $conf['smokingAllowed'] ?? false,
    //             'allow_bookings' => $conf['allowBookings'] ?? true,
    //             'allow_enquiries' => $conf['allowEnquiries'] ?? true,
    //         ]
    //     );

    //     return response()->json(['message' => 'Configuration saved successfully', 'data' => $record]);
    // }

    // public function getSingleConfiguration(Request $request)
    // {
    //     $propertyId = $request->input('propertyId');

    //     if (! $propertyId) {
    //         return response()->json(['message' => 'propertyId is required'], 400);
    //     }

    //     $record = SingleConfiguration::where('property_id', $propertyId)->first();

    //     if ($record) {
    //         return response()->json(['message' => 'Configuration fetched successfully', 'data' => $record]);
    //     }

    //     return response()->json(['message' => 'No configuration found for the given propertyId'], 404);
    // }

    public function fetchAndSavePropertyConfiguration($propertyId)
    {
        $propertyLastUpdated = '2021-04-01T00:00:00Z';
        $url = "https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/{$propertyId}?propertyLastUpdated=".urlencode($propertyLastUpdated);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            return response()->json(['error' => 'cURL Error: '.$err]);
        }

        $data = json_decode($response, true);

        if (! $data || ! isset($data['propertyId'])) {
            return response()->json(['error' => 'Invalid or missing response from API']);
        }

        $conf = $data['configuration'] ?? [];

        $record = SingleConfiguration::updateOrCreate(
            ['property_id' => $data['propertyId']],
            [
                'property_url' => $data['propertyUrl'] ?? null,
                'customer_property_id' => $data['customerPropertyId'] ?? null,
                'card_payment_forms' => $conf['acceptedPaymentForms']['cardPaymentForms'] ?? [],
                'cancellation_policy' => $conf['cancellationPolicy']['policy'] ?? null,
                'check_in_time' => $conf['checkInTime'] ?? null,
                'check_out_time' => $conf['checkOutTime'] ?? null,
                'children_allowed' => $conf['childrenAllowed'] ?? false,
                'max_adults' => $conf['maximumOccupancy']['adults'] ?? null,
                'max_guests' => $conf['maximumOccupancy']['guests'] ?? null,
                'max_children' => $conf['maximumOccupancy']['children'] ?? null,
                'merchant_name' => $conf['merchantName'] ?? null,
                'pets_allowed' => $conf['petsAllowed'] ?? false,
                'smoking_allowed' => $conf['smokingAllowed'] ?? false,
                'allow_bookings' => $conf['allowBookings'] ?? true,
                'allow_enquiries' => $conf['allowEnquiries'] ?? true,
            ]
        );

        return response()->json([
            'message' => 'Property configuration saved successfully!',
            'data' => $record,
        ]);
    }

    



    public function fetchAndStorePricing($propertyId)
{
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.supercontrol.co.uk/v3/properties/prices/{$propertyId}?propertyLastUpdated=2025-04-29T04%3A35%3A08Z",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'SC-TOKEN: cddc35d0-1491-4b04-969c-9f8419ef0e99',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $data = json_decode($response, true);

    if (! $data || ! isset($data['prices'])) {
        return redirect()->back()->with('error', 'Invalid response from API.');
    }

    
    $this->savePriceData($data);

    
    return view('price.listing.blade', [  
        'priceData' => $data,
        'updatedAt' => now(),
    ]);
}


    private function savePriceData(array $data)
    {

        $price = $data['prices'];

        $config = PriceConfiguration::updateOrCreate(
            ['property_id' => $price['propertyId']],
            [
                'property_id' => $price['propertyId'], // This should match the key from $price
                'currency' => $price['currency'],
                'deposit_type' => $price['depositType'] ?? null,
                'deposit_value' => $price['depositValue'] ?? null,
                'balance_due_days' => $price['balanceDueDays'] ?? null,
                'security_deposit' => $price['securityDeposit'] ?? null,
                'last_updated' => Carbon::now()->toDateTimeString(),
            ]
        );

        return response()->json([
            'message' => 'Price data processing logic not yet implemented',
            'rawData' => $data,
        ]);
    }

    public function fetchAndStore()
    {
        $response = Http::withHeaders([
            'SC-TOKEN' => 'cddc35d0-1491-4b04-969c-9f8419ef0e99',
            'Accept' => 'application/json',
        ])->get('https://api.supercontrol.co.uk/v3/properties/PropertyConfiguration/636608?propertyLastUpdated=2021-04-01T00%3A00%3A00Z');

        if ($response->successful()) {
            $data = $response->json();

            $listing = ContentListing::create([
                'property_id' => $data['propertyId'],
                'property_url' => $data['propertyUrl'],
                'customer_property_id' => $data['customerPropertyId'],
                'accepted_payment_forms' => $data['configuration']['acceptedPaymentForms'],
                'cancellation_policy' => $data['configuration']['cancellationPolicy']['policy'],
                'check_in_time' => $data['configuration']['checkInTime'],
                'check_out_time' => $data['configuration']['checkOutTime'],
                'children_allowed' => $data['configuration']['childrenAllowed'],
                'maximum_adults' => $data['configuration']['maximumOccupancy']['adults'],
                'maximum_guests' => $data['configuration']['maximumOccupancy']['guests'],
                'maximum_children' => $data['configuration']['maximumOccupancy']['children'],
                'merchant_name' => $data['configuration']['merchantName'],
                'pets_allowed' => $data['configuration']['petsAllowed'],
                'smoking_allowed' => $data['configuration']['smokingAllowed'],
                'allow_bookings' => $data['configuration']['allowBookings'],
                'allow_enquiries' => $data['configuration']['allowEnquiries'],
            ]);

            return response()->json(['message' => 'Content listing fetched and stored successfully!', 'listing' => $listing]);
        } else {
            return response()->json(['error' => 'Failed to fetch data from external API.'], 500);
        }
    }
}
