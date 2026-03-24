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
        $query = PropertyContent::query()
            ->with(['images', 'regions', 'content_listings'])
            ->where('property_contents.active', 1);

        if ($request->filled('collection_id')) {
            $query->join('collection_property', 'property_contents.id', '=', 'collection_property.property_id')
                ->where('collection_property.collection_id', $request->collection_id)
                ->select('property_contents.*');
        }

        if ($request->filled('region_id')) {
            $regionIds = is_array($request->region_id)
                ? $request->region_id
                : explode(',', $request->region_id);

            $query->whereIn('property_contents.id', function ($sub) use ($regionIds) {
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
        if ($request->filled('date_availability')) {
            $dateRange = $request->date_availability;
            if (is_string($dateRange) && strpos($dateRange, '/') !== false) {
                [$start, $end] = explode('/', $dateRange);
                $checkIn = Carbon::parse($start)->startOfDay();
                $checkOut = Carbon::parse($end)->startOfDay();
            } elseif (is_array($dateRange) && isset($dateRange['start_date'], $dateRange['end_date'])) {
                $checkIn = Carbon::parse($dateRange['start_date'])->startOfDay();
                $checkOut = Carbon::parse($dateRange['end_date'])->startOfDay();
            }

            if ($checkIn && $checkOut) {
                $nights = $checkIn->diffInDays($checkOut);
                if ($nights > 0) {
                    // Filter properties available for ALL days in range
                    $query->where(function($q) use ($checkIn, $checkOut, $nights) {
                        $q->whereIn('property_id', function($sub) use ($checkIn, $checkOut, $nights) {
                            $sub->select('listing_id')
                                ->from('property_calendars')
                                ->whereBetween('date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
                                ->where('is_available', 1)
                                ->groupBy('listing_id')
                                ->havingRaw('COUNT(*) = ?', [$nights]);
                        });
                    });
                }
            }
        }

        $properties = $query->get();

        // Fetch prices for the filtered properties
        $propertyPrices = [];
        if ($checkIn && $checkOut) {
            $listingIds = $properties->pluck('property_id')->toArray();
            
            // Get prices from local DB (fastest)
            $dbPrices = \DB::table('property_calendars')
                ->whereIn('listing_id', $listingIds)
                ->whereBetween('date', [$checkIn->toDateString(), $checkOut->copy()->subDay()->toDateString()])
                ->select('listing_id', \DB::raw('SUM(price) as total'), \DB::raw('AVG(price) as average'))
                ->groupBy('listing_id')
                ->get()
                ->keyBy('listing_id');

            foreach ($properties as $property) {
                $pid = $property->property_id;
                $priceData = $dbPrices->get($pid);

                if ($priceData && $priceData->total > 0) {
                    $propertyPrices[$pid] = [
                        'total_price' => round($priceData->total, 2),
                        'price_per_night' => round($priceData->average, 2)
                    ];
                } else {
                    // Fallback to Live API ONLY if DB price is missing or 0
                    // This prevents timeouts by only hitting API for a few properties
                    try {
                        $listingId = !empty($property->hostaway_id) ? $property->hostaway_id : $pid;
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                        ])->timeout(5)->get("https://api.hostaway.com/v1/listings/{$listingId}/calendar", [
                            'startDate' => $checkIn->format('Y-m-d'),
                            'endDate'   => $checkOut->format('Y-m-d'),
                        ]);

                        if ($response->successful() && isset($response->json()['result'])) {
                            $calendarData = $response->json()['result'];
                            $total = 0; $count = 0;
                            foreach ($calendarData as $day) {
                                $dayDate = Carbon::parse($day['date'])->startOfDay();
                                if ($dayDate >= $checkIn && $dayDate < $checkOut && isset($day['price'])) {
                                    $total += (float)$day['price'];
                                    $count++;
                                }
                            }
                            if ($count > 0) {
                                $propertyPrices[$pid] = [
                                    'total_price' => round($total, 2),
                                    'price_per_night' => round($total / $count, 2)
                                ];
                            }
                        }
                    } catch (\Exception $e) {}
                }
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
