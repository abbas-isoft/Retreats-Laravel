<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;


class PropertyGridController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\PropertyContent::query()
            ->with(['images', 'regions', 'content_listings'])
            ->where('active', 1);

        if ($request->filled('collection_id')) {
            $query->join('collection_property', 'property_contents.property_id', '=', 'collection_property.property_id')
                ->where('collection_property.collection_id', $request->collection_id)
                ->select('property_contents.*');
        }

        if ($request->filled('region_id')) {
            $regionIds = is_array($request->region_id)
                ? $request->region_id
                : explode(',', $request->region_id);

            $query->whereIn('id', function ($sub) use ($regionIds) {
                $sub->select('pc.id')
                    ->from('property_contents as pc')
                    ->join('properties as p', 'p.id', '=', 'pc.property_id')
                    ->join('region_property as rp', 'rp.property_id', '=', 'p.id')
                    ->whereIn('rp.region_id', $regionIds);
            });
        }

        if ($request->filled('address_line')) {
            $query->where('address_line', 'LIKE', '%' . $request->address_line . '%');
        }

        if ($request->filled('guest_capacity')) {
            $query->whereHas('content_listings', function ($q) use ($request) {
                $q->where('maximum_guests', '>=', $request->guest_capacity);
            });
        }

        if ($request->filled('amenities')) {
            $amenities = is_array($request->amenities)
                ? $request->amenities
                : explode(',', $request->amenities);
            $amenities = array_map('trim', $amenities);

            foreach ($amenities as $amenity) {
                $query->where(function ($q) use ($amenity) {
                    $q->where('amenities', 'LIKE', '%"' . $amenity . '"%')
                        ->orWhere('amenities', 'LIKE', '%' . $amenity . '%');
                });
            }
        }

        if ($request->filled('city')) {
            $query->where('city', 'LIKE', '%' . $request->city . '%');
        }

        if ($request->filled('region')) {
            $query->whereIn('property_contents.property_id', function ($sub) use ($request) {
                $sub->select('rp.property_id')
                    ->from('region_property as rp')
                    ->join('regions as r', 'r.id', '=', 'rp.region_id')
                    ->where('r.active', 1)
                    ->where('r.name', 'LIKE', '%' . $request->region . '%');
            });
        }

        if ($request->filled('search')) {
            $query->where('property_name', 'LIKE', '%' . $request->search . '%');
        }

        $checkIn = null;
        $checkOut = null;
        $nights = 0;

        if ($request->filled('date_availability')) {
            $dates = $request->date_availability;
            if (is_string($dates) && str_contains($dates, ',')) {
                $parts = explode(',', $dates);
                if (count($parts) >= 2) {
                    $checkIn = Carbon::parse($parts[0])->startOfDay();
                    $checkOut = Carbon::parse($parts[1])->startOfDay();
                }
            } elseif (is_array($dates) && isset($dates['start_date'], $dates['end_date'])) {
                $checkIn = Carbon::parse($dates['start_date'])->startOfDay();
                $checkOut = Carbon::parse($dates['end_date'])->startOfDay();
            }
        } elseif ($request->filled(['check_in', 'check_out'])) {
            $checkIn = Carbon::parse($request->check_in)->startOfDay();
            $checkOut = Carbon::parse($request->check_out)->startOfDay();
        } elseif ($request->filled(['start_date', 'end_date'])) {
            $checkIn = Carbon::parse($request->start_date)->startOfDay();
            $checkOut = Carbon::parse($request->end_date)->startOfDay();
        }

        if ($checkIn && $checkOut) {
            $nights = $checkIn->diffInDays($checkOut);
            if ($nights > 0) {
                $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut, $nights) {
                    $q->where('start_date', '<=', $checkIn)
                        ->where('end_date', '>=', $checkOut)
                        ->whereRaw("SUBSTRING(availability, DATEDIFF(?, start_date) + 1, ?) = REPEAT('Y', ?)", [
                            $checkIn->format('Y-m-d'), $nights, $nights
                        ]);
                });
            }
        }

        $properties = $query->get();

        // Fetch prices for the filtered properties if dates are provided
        $propertyPrices = [];
        if ($checkIn && $checkOut && $nights > 0) {
            $nights = $checkIn->diffInDays($checkOut);
            foreach ($properties as $property) {
                $pid = $property->property_id;
                $listingId = !empty($property->hostaway_id) ? $property->hostaway_id : $pid;

                $totalPrice = 0;
                $pricePerNight = 0;

                try {
                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                        'Content-Type' => 'application/json',
                    ])->timeout(30)->get(
                        "https://api.hostaway.com/v1/listings/{$listingId}/calendar",
                        [
                            'startDate' => $checkIn->format('Y-m-d'),
                            'endDate'   => $checkOut->format('Y-m-d'),
                        ]
                    );

                    if ($response->successful() && isset($response->json()['result'])) {
                        $calendarData = $response->json()['result'];
                        if (is_array($calendarData) && count($calendarData) > 0) {
                            $dailyPrices = [];
                            foreach ($calendarData as $day) {
                                if (!isset($day['date'])) continue;
                                $dayDate = Carbon::parse($day['date'])->startOfDay();
                                if ($dayDate >= $checkIn && $dayDate < $checkOut) {
                                    if (isset($day['price']) && is_numeric($day['price']) && $day['price'] > 0) {
                                        $dailyPrice = (float) $day['price'];
                                        $dailyPrices[] = $dailyPrice;
                                        $totalPrice += $dailyPrice;
                                    }
                                }
                            }
                            if (count($dailyPrices) > 0) {
                                $pricePerNight = $totalPrice / count($dailyPrices);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::debug("Price fetch error for property {$listingId}: " . $e->getMessage());
                }

                // Fallback: If no price found from calendar, try listing endpoint
                if ($totalPrice == 0) {
                    try {
                        $listingResponse = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                            'Content-Type' => 'application/json',
                        ])->timeout(20)->get(
                            "https://api.hostaway.com/v1/listings/{$listingId}"
                        );

                        $listingData = $listingResponse->json();
                        if (isset($listingData['result']['price']) && is_numeric($listingData['result']['price'])) {
                            $pricePerNight = (float) $listingData['result']['price'];
                            $totalPrice = $pricePerNight * $nights;
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::debug("Fallback price fetch error for property {$listingId}: " . $e->getMessage());
                    }
                }

                $propertyPrices[$pid] = [
                    'total_price' => round($totalPrice, 2),
                    'price_per_night' => round($pricePerNight, 2)
                ];
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'data' => [
                'properties' => $properties->map(function ($property) use ($propertyPrices) {
                    if (is_string($property->amenities)) {
                        $property->amenities = json_decode($property->amenities, true);
                    }
                    $property->location_description = Str::limit($property->location_description, 50);

                    $listing = $property->content_listings->first();
                    $maxGuests = $listing ? $listing->maximum_guests : null;
                    $property->unsetRelation('content_listings');

                    $priceData = $propertyPrices[$property->property_id] ?? ['price_per_night' => 0, 'total_price' => 0];

                    $propertyArray = $property->toArray();
                    $orderedProperty = [];
                    foreach ($propertyArray as $key => $value) {
                        $orderedProperty[$key] = $value;
                        if ($key === 'area_unit') {
                            $orderedProperty['maximum_guests'] = $maxGuests;
                            $orderedProperty['price_per_night'] = $priceData['price_per_night'];
                            $orderedProperty['total_price'] = $priceData['total_price'];
                        }
                    }

                    if (!empty($property->custom_description)) {
                        $orderedProperty['description'] = $property->custom_description;
                    }

                    if (!array_key_exists('maximum_guests', $orderedProperty)) {
                        $orderedProperty['maximum_guests'] = $maxGuests;
                        $orderedProperty['price_per_night'] = $priceData['price_per_night'];
                        $orderedProperty['total_price'] = $priceData['total_price'];
                    }

                    return $orderedProperty;
                }),
                'total_properties' => $properties->count()
            ],
        ]);
    }
}