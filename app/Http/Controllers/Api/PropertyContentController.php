<?php

namespace App\Http\Controllers\Api;

use App\Models\AvailabilityCalendar;
use App\Http\Resources\PropertyRoomBedsResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\PropertyContentResource;
use App\Models\Property;
use App\Models\PropertyContent;
use App\Models\Collection;
use App\Models\PropertyContentImage;
use App\Models\Review;
use App\Models\ContentListing;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PriceConfiguration;
use App\Models\PriceLosEntry;
use App\Models\PropertyExtra;
class PropertyContentController extends Controller
{

    public function index($propertyId): JsonResponse
    {
        try {
            $property = Property::find($propertyId);

            if (! $property) {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not found',
                ]);
            }

            $contents = PropertyContent::where('property_id', $propertyId)
                ->latest()
                ->get();

                // ✅ Reviews
            $reviews = Review::where('property_id', $propertyId)
                ->latest()
                ->get();

            // ✅ Content Listings
            $contentListings = ContentListing::where('property_id', $propertyId)
            ->latest()
            ->get();
//            // ✅ Similar Properties (same city + same post_code)
// $similarProperties = DB::table('property_contents')
//     ->where('city', $property->city) // same city
//     ->where('post_code', $property->post_code) // same postcode
//     ->where('id', '!=', $property->id) // exclude current property
//     ->limit(5) // top 5 similar
//     ->get();

            if ($contents->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => 'No Property Content data found.',
                    'property_content' => [],
                ]);
            }

            return response()->json([
    'status' => true,
    'message' => 'Property content fetched successfully.',
    'data' => PropertyContentResource::collection($contents)->values(), // flatten numeric keys
]);
        } catch (Exception $e) {
            Log::error('Property content fetch failed: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching property content.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ✅ New method: Fetch all property locations for map display
    // public function getLocations(): JsonResponse
    // {
    //     try {
    //         $locations = PropertyContent::whereNotNull('latitude')
    //             ->whereNotNull('longitude')
    //             ->get([
    //                 'id',
    //                 'property_name',
    //                 'location_description',
    //                 'latitude',
    //                 'longitude',
    //                 'city',
    //                 'country'
                    
    //             ]);

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Map locations fetched successfully.',
    //             'data' => $locations,
    //         ]);
    //     } catch (Exception $e) {
    //         Log::error('Location fetch failed: ' . $e->getMessage());

    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Something went wrong while fetching map locations.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }


    public function getLocations(): JsonResponse
{
    try {
        // Get all properties with coordinates
        $locations = PropertyContent::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // Map each location and include all its image URLs from the `url` column
        $data = $locations->map(function ($location) {
            // Get all images for this property from `property_content_images`
            $images = PropertyContentImage::where('property_content_id', $location->id)->get();

            // Extract just the `url` values
            $imageUrls = $images->pluck('url');  // pluck returns only the column you need

            return [
                'id' => $location->id,
                'property_name' => $location->property_name,
                'location_description' => $location->location_description,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'post_code' => $location->post_code,
                'city' => $location->city,
                'country' => $location->country,
                'images' => $imageUrls,  // full URLs from DB
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Map locations fetched successfully.',
            'data' => $data,
        ]);
    } catch (Exception $e) {
        Log::error('Location fetch failed: ' . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Something went wrong while fetching map locations.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function getCollection($id): JsonResponse
    {
        // Find the Collection by its ID
        $collection = Collection::find($id);

        // If the Collection doesn't exist, return an error message
        if (!$collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found by ID',
            ], 404);
        }

        // Get all PropertyContentImage entries that belong to this collection
        $collectionRelatedImages = PropertyContentImage::where('property_content_id', $collection->id)->get();

        // Prepare the images data, replacing property_content_id with collection title
        $imagesWithTitle = $collectionRelatedImages->map(function ($image) use ($collection) {
            $image->collection_title = $collection->title;  // Add the title to the image data
            return $image;
        });

        return response()->json([
            'status' => true,
            'message' => 'Images related to the Collection: ' . $collection->title,
            'data' => $imagesWithTitle
        ], 200);
    }



//    public function getSimilar(Request $request, $propertyId)
// {
//     try {
//         //  Find main property
//         $property = PropertyContent::where('property_id',$propertyId)->first();

//         if (!$property) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Property not found',
//             ], 404);
//         }

//         // Query similar properties (same city + post_code, exclude current)
//         $similarProperties = PropertyContent::with(['images', 'listing'])
//             ->where('id', '!=', $property->id)
//             ->where('city', $property->city)
//             ->where('property_id', '!=', $propertyId)
//             ->where('post_code', $property->post_code)
//             ->take(10)
//             ->get();

//         //  Format amenities & location description
//         $similarProperties = $similarProperties->map(function ($property) {
//             if (is_string($property->amenities)) {
//                 $property->amenities = json_decode($property->amenities, true);
//             }
//             $property->location_description = Str::limit($property->location_description, 50);
//             return $property;
//         });

//         //  Return response
//         return response()->json([
//             'status' => true,
//             'message' => 'Similar properties fetched successfully.',
//             'data' => [
//                 'properties' => $similarProperties
//             ]
//         ], 200);

//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => 'Something went wrong while fetching similar properties.',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// }



public function getSimilar(Request $request, $propertyId)
{
    try {
        // Find main property
        $property = PropertyContent::where('property_id', $propertyId)->first();

        if (!$property) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found',
            ], 404);
        }

        // Decode amenities JSON for comparison
        $propertyAmenities = [];
        if (is_string($property->amenities)) {
            $propertyAmenities = json_decode($property->amenities, true) ?? [];
        }

        // Query similar properties based on property_type, bedrooms, bathrooms, and amenities
        $similarProperties = PropertyContent::with(['images', 'listing'])
            ->where('id', '!=', $property->id)
            ->where('property_type', $property->property_type)
            ->whereBetween('bedroom_count', [$property->bedroom_count - 1, $property->bedroom_count + 1])
            ->whereBetween('bathroom_count', [$property->bathroom_count - 1, $property->bathroom_count + 1])
            ->take(10)
            ->get()
            ->map(function ($similar) use ($propertyAmenities) {
                // Decode amenities JSON if needed
                if (is_string($similar->amenities)) {
                    $similarAmenities = json_decode($similar->amenities, true) ?? [];
                    // Optional: keep only common amenities
                    $similar->amenities = array_values(array_intersect($propertyAmenities, $similarAmenities));
                } else {
                    $similar->amenities = [];
                }

                // Shorten location description
                $similar->location_description = Str::limit($similar->location_description, 50);
                return $similar;
            });

        // Return response
        return response()->json([
            'status' => true,
            'message' => 'Similar properties fetched successfully.',
            'data' => [
                'similar_properties' => $similarProperties
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong while fetching similar properties.',
            'error' => $e->getMessage(),
        ], 500);
    }
}



public function getPropertyPrice(Request $request, $propertyId)
{
    // Get check-in and check-out from request
    $checkIn  = Carbon::parse($request->check_in);
    $checkOut = Carbon::parse($request->check_out);

    // Find property
    $property = Property::find($propertyId);

    if (!$property) {
        return response()->json([
            'status' => false,
            'message' => 'Property not found',
        ], 404);
    }

    // Number of nights (stay duration)
    $nights = $checkIn->diffInDays($checkOut);

    // Price per night (assume column is price_per_night)
    $pricePerNight = $property->price_per_night;

    // Total price
    $totalPrice = $nights * $pricePerNight;

    return response()->json([
        'status' => true,
        'property_id' => $property->id,
        'check_in' => $checkIn->toDateString(),
        'check_out' => $checkOut->toDateString(),
        'nights' => $nights,
        'price_per_night' => $pricePerNight,
        'total_price' => $totalPrice,
    ]);
}

public function GetBookingInfo(Request $request, $propertyId)
{
    //  Property record
    $property = Property::findOrFail($propertyId);

    $contentListing = \App\Models\ContentListing::where('property_id', $propertyId)->first();

    if (!$contentListing) {
        $extras = collect(); // empty collection
    } else {
        // Step 2: get all property_rooms for this content listing
        $roomIds = \App\Models\PropertyRoom::where('property_content_id', $contentListing->id)
            ->pluck('id');

        // Step 3: get all beds for those rooms
        $extras = \App\Models\PropertyRoomBed::whereIn('property_room_id', $roomIds)->get();
    }

    // Step 4: map to resource
    $extras = PropertyRoomBedsResource::collection($extras);

    //  Settings (from contents_listing)
    $settings = [
        'pets_allowed'    => $contentListing->pets_allowed ?? null,
        'smoking_allowed' => $contentListing->smoking_allowed ?? null,
        'allow_bookings'  => $contentListing->allow_bookings ?? null,
        'allow_enquiries' => $contentListing->allow_enquiries ?? null,
    ];

    //  Billing (from price_configurations)
    $priceConfig = \App\Models\PriceConfiguration::where('property_id', $propertyId)->first();

    $balanceDueDays  = $priceConfig->balance_due_days ?? 0;
    $depositType     = $priceConfig->deposit_type ?? null;
    $depositValue    = $priceConfig->deposit_value ?? 0;
    $securityDeposit = $priceConfig->security_deposit ?? 0;

    //  Booking Inputs (from frontend)
    $checkIn  = Carbon::parse(trim($request->check_in))->startOfDay();
    $checkOut = Carbon::parse(trim($request->check_out))->startOfDay();
    $nights   = $checkIn->diffInDays($checkOut);

    if ($nights < 1) {
        return response()->json([
            'status' => false,
            'message' => 'Check-out must be after Check-in.',
        ]);
    }

    //  Calculate price (same logic as calculatePrice)
    $entry = PriceLosEntry::where('price_configuration_id', $priceConfig->id)
        ->where('date', $checkIn->toDateString())
        ->first();

    if (!$entry) {
        return response()->json([
            'status' => false,
            'message' => 'No pricing data found for this date'
        ]);
    }

    // Availability check
    $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();
    if (!$calendar) {
        return response()->json([
            'status' => false,
            'message' => 'No availability calendar found.'
        ]);
    }

    $availability = $calendar->availability; // "NYYNNYY..." string
    $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
    $calendarEnd   = Carbon::parse($calendar->end_date)->startOfDay();

    if ($checkIn < $calendarStart || $checkOut > $calendarEnd) {
        return response()->json([
            'status' => false,
            'message' => 'Selected dates are outside availability calendar.'
        ]);
    }

    $currentDate = $checkIn->copy();
    $available = true;
    while ($currentDate < $checkOut) {
        $dayIndex = $calendarStart->diffInDays($currentDate);
        $status   = $availability[$dayIndex - 1] ?? 'N';
        if ($status === 'N') {
            $available = false;
            break;
        }
        $currentDate->addDay();
    }

    if (!$available) {
        return response()->json([
            'status' => false,
            'message' => 'Property Not Available for the selected dates',
        ]);
    }

    // Price extraction
    $prices = explode(',', trim($entry->length_of_stay_prices, '"'));
    $guests = $prices[1] ?? 0;
    $prices = array_slice($prices, 2);
    $totalPrice = $prices[$nights - 1] ?? 0;

    if ($totalPrice == 0) {
        return response()->json([
            'status' => false,
            'message' => 'Property Not Available for ' . $nights . ' nights',
        ]);
    }

    //  Balance Due Date
    $balanceDueDate = $checkIn->copy()->subDays($balanceDueDays)->format('Y-m-d');

    //  Days until arrival
    $daysUntilArrival = now()->diffInDays($checkIn, false);

    //  Deposit & Balance Calculation
    $depositAmount = 0;
    $balanceAmount = 0;
    $depositLabel  = "Due Now";
    $balanceLabel  = null;

    if ($daysUntilArrival > 62) {
        if ($depositType === 'Percentage') {
            $depositAmount = round(($depositValue / 100) * $totalPrice, 2);
        } else {
            $depositAmount = $depositValue;
        }

        if ($depositAmount <= 0) {
            $depositAmount = round(0.25 * $totalPrice, 2);
        }

        $balanceAmount = $totalPrice - $depositAmount;

        $depositLabel = "Due Now";
        $balanceLabel = "Charged on " . $balanceDueDate;
    } else {
        $depositAmount = $totalPrice;
        $balanceAmount = 0;

        $depositLabel = "Due Now";
        $balanceLabel = null;
    }
$extras = \App\Models\PropertyExtra::where('property_id', $propertyId)->get();

    //  Final Response
    return response()->json([
    'status'  => true,
    'message' => 'Booking info fetched successfully',
    'data'    => [
        'property_id' => $propertyId,
        'extras'      => PropertyExtra::where('property_id', $propertyId)->get(),
        'settings'    => $settings,
        'billing'     => [
            'deposit_type'     => $depositType,
            'deposit_value'    => $depositValue,
            'security_deposit' => $securityDeposit,
            'total_price'      => $totalPrice,
            'nights'          => $nights,
            'deposit' => [
                // 'label'  => $depositLabel,
                // 'amount' => $depositAmount
                //    'due_date' => $balanceAmount > 0 ? $balanceDueDate : null,
                // 'label'    => 'Due Now',
                'amount'   => $balanceAmount
            ],
            'balance' => [
                 'label'  => 'Due Now',
                  'due_date' => $balanceAmount > 0 ? $balanceDueDate : null,
                'amount' => $depositAmount
            ]
        ]
    ]
]);
}



public function getOtherProperties(Request $request)
{
    $request->validate([
        'property_id' => 'required|integer|exists:properties,id',
        'check_in'    => 'required|date',
        'check_out'   => 'required|date|after:check_in',
    ]);

    $propertyId = $request->property_id;
    $checkIn = Carbon::parse($request->check_in)->startOfDay();
    $checkOut = Carbon::parse($request->check_out)->startOfDay();
    $nights = $checkIn->diffInDays($checkOut);

    // Check availability for the requested property first
    $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();
    if (!$calendar) {
        return response()->json([
            'status' => false,
            'message' => 'Property Not Available for the selected dates',
            'unavailable_date' => $checkIn->toDateString()
        ]);
    }

    $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
    $calendarEnd = Carbon::parse($calendar->end_date)->startOfDay();
    $availability = $calendar->availability;

    if ($checkIn < $calendarStart || $checkOut > $calendarEnd) {
        return response()->json([
            'status' => false,
            'message' => 'Property Not Available for the selected dates',
            'unavailable_date' => $checkIn->toDateString()
        ]);
    }

    // Check day-by-day availability
    $currentDate = $checkIn->copy();
    while ($currentDate < $checkOut) {
        $dayIndex = $calendarStart->diffInDays($currentDate);
        $status = $availability[$dayIndex - 1] ?? 'N';
        if ($status === 'N') {
            return response()->json([
                'status' => false,
                'message' => 'Property Not Available for the selected dates',
                'unavailable_date' => $currentDate->toDateString()
            ]);
        }
        $currentDate->addDay();
    }

    // If available, fetch other properties
    $otherProperties = Property::where('id', '!=', $propertyId)->get();
    $availableProperties = [];

    foreach ($otherProperties as $property) {
        $calendar = AvailabilityCalendar::where('property_id', $property->id)->first();
        if (!$calendar) continue;

        $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
        $calendarEnd = Carbon::parse($calendar->end_date)->startOfDay();
        $availability = $calendar->availability;

        if ($checkIn < $calendarStart || $checkOut > $calendarEnd) continue;

        $currentDate = $checkIn->copy();
        $isAvailable = true;
        while ($currentDate < $checkOut) {
            $dayIndex = $calendarStart->diffInDays($currentDate);
            $status = $availability[$dayIndex - 1] ?? 'N';
            if ($status === 'N') {
                $isAvailable = false;
                break;
            }
            $currentDate->addDay();
        }

        if ($isAvailable) {
            $content = PropertyContent::where('property_id', $property->id)->first();
            $propertyName = $content ? $content->property_name : null;

            $images = $content
                ? PropertyContentImage::where('property_content_id', $content->id)
                    ->pluck('url')
                    ->toArray()
                : [];

            $config = PriceConfiguration::where('property_id', $property->id)->first();
            $entry = $config ? PriceLosEntry::where('price_configuration_id', $config->id)
                ->where('date', $checkIn->toDateString())
                ->first() : null;

            $totalPrice = 0;
            if ($entry) {
                $prices = explode(',', trim($entry->length_of_stay_prices, '"'));
                $price = array_slice($prices, 2)[$nights - 1] ?? 0;
                $totalPrice = $price;
            }

            $availableProperties[] = [
                'id' => $property->id,
                'name' => $propertyName,
                'total_price' => $totalPrice,
                'nights' => $nights,
                'check_in' => $checkIn->toDateString(),
                'check_out' => $checkOut->toDateString(),
                'images' => $images,
            ];
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Available properties fetched successfully',
        'data' => $availableProperties
    ]);
}

}


