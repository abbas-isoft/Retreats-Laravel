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
        }

        $properties = $query->get();

        $filteredProperties = [];
        $propertyPrices = [];

        if ($checkIn && $checkOut && $nights > 0) {
            foreach ($properties as $property) {
                $pid = $property->property_id;
                $listingId = !empty($property->hostaway_id) ? $property->hostaway_id : $pid;

                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(60)->retry(3, 2000)
                        ->get("https://booking-engine.hostaway.com/bookingEngines/booking.groupretreats.co.uk/listings/{$listingId}/calendar", [
                            'startingDate' => $checkIn->format('Y-m-d'),
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $calendarResult = $data['result'] ?? [];

                        if (!empty($calendarResult)) {
                            $isAvailableInRange = true;
                            $totalPriceInRange = 0;
                            $daysFound = 0;

                            $dailyData = [];
                            foreach ($calendarResult as $day) {
                                $dailyData[$day['date']] = $day;
                            }

                            $currentDate = $checkIn->copy();
                            while ($currentDate < $checkOut) {
                                $dStr = $currentDate->format('Y-m-d');
                                $dayInfo = $dailyData[$dStr] ?? null;

                                if (!$dayInfo) {
                                    $isAvailableInRange = false;
                                    break;
                                }

                                $isDayAvailable = ($dayInfo['isAvailable'] ?? 0) == 1;
                                $status = $dayInfo['status'] ?? 'unavailable';

                                if (!$isDayAvailable || in_array($status, ['reserved', 'unavailable', 'blocked'])) {
                                    $isAvailableInRange = false;
                                    break;
                                }

                                if (isset($dayInfo['price']) && is_numeric($dayInfo['price'])) {
                                    $totalPriceInRange += (float) $dayInfo['price'];
                                    $daysFound++;
                                }

                                $currentDate->addDay();
                            }

                            if ($isAvailableInRange) {
                                $pricePerNight = $daysFound > 0 ? $totalPriceInRange / $daysFound : 0;
                                $propertyPrices[$pid] = [
                                    'total_price' => round($totalPriceInRange, 2),
                                    'price_per_night' => round($pricePerNight, 2)
                                ];
                                $filteredProperties[] = $property;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Live API fetch error for property {$listingId}: " . $e->getMessage());
                }
            }
            $properties = collect($filteredProperties);
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