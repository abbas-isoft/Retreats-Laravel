<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PropertyGridController extends Controller
{
    public function index(Request $request)
    {
        // Get dates for API call
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Robust date parsing: handle multiple formats including duplicate query keys
        if (!$startDate || !$endDate) {
            $queryString = $_SERVER['QUERY_STRING'] ?? '';
            if (strpos($queryString, 'date_availability=') !== false) {
                // Manually parse query string for duplicate 'date_availability' keys
                parse_str($queryString, $parsedParams);

                // If Laravel's parse_str also overwrites, we can do manual extraction
                $matches = [];
                preg_match_all('/date_availability=([^&]+)/', $queryString, $matches);
                if (isset($matches[1]) && count($matches[1]) >= 2) {
                    $startDate = $matches[1][0];
                    $endDate = $matches[1][1];
                }
            }
        }

        // Fallback to array/string handling if start/end still null
        if (!$startDate && $request->has('date_availability')) {
            $dateAvail = $request->input('date_availability');
            if (is_array($dateAvail)) {
                if (isset($dateAvail['start_date']) && isset($dateAvail['end_date'])) {
                    $startDate = $dateAvail['start_date'];
                    $endDate = $dateAvail['end_date'];
                } else {
                    $startDate = $dateAvail[0] ?? null;
                    $endDate = $dateAvail[1] ?? null;
                }
            } else if (is_string($dateAvail)) {
                // If it's a comma-separated string or slash-separated string
                if (strpos($dateAvail, ',') !== false) {
                    $dates = explode(',', $dateAvail);
                    $startDate = trim($dates[0]);
                    $endDate = trim($dates[1] ?? '');
                } else if (strpos($dateAvail, '/') !== false) {
                    $dates = explode('/', $dateAvail);
                    $startDate = trim($dates[0]);
                    $endDate = trim($dates[1] ?? '');
                } else {
                    // It's a single date string (like in the user's screenshot)
                    $startDate = trim($dateAvail);
                    // Default to 3 nights stay if only start date provided
                    try {
                        $endDate = Carbon::parse($startDate)->addDays(3)->toDateString();
                    } catch (\Exception $e) {
                        $startDate = null;
                        $endDate = null;
                    }
                }
            }
        }

        $numberOfGuests = $request->input('guest_capacity', 1);

        $query = \App\Models\PropertyContent::query()
            ->with(['images', 'regions', 'content_listings'])
            ->where('active', 1);

        if ($request->filled('collection_id')) {
            $query->join('collection_property', 'property_contents.id', '=', 'collection_property.property_id') // ✅ correct join
                ->where('collection_property.collection_id', $request->collection_id)
                ->select('property_contents.*');
        }

        if ($request->filled('region_id')) {
            $regionIds = is_array($request->region_id) ? $request->region_id : explode(',', $request->region_id);
            $query->whereIn('property_contents.property_id', function ($sub) use ($regionIds) {
                $sub->select('property_id')->from('region_property')->whereIn('region_id', $regionIds);
            });
        }

        if ($request->filled('region')) {
            $regionName = $request->region;
            $query->whereIn('property_contents.property_id', function ($sub) use ($regionName) {
                $sub->select('rp.property_id')
                    ->from('region_property as rp')
                    ->join('regions as r', 'r.id', '=', 'rp.region_id')
                    ->where('r.name', 'LIKE', '%' . $regionName . '%');
            });
        }

        if ($request->filled('address_line')) {
            $query->where('address_line', 'LIKE', '%' . $request->address_line . '%');
        }

        if ($request->filled('guest_capacity')) {
            $guestCapacity = $request->guest_capacity;
            $isPlus = str_contains($guestCapacity, '+');

            $query->whereHas('content_listings', function ($q) use ($guestCapacity, $isPlus) {
                $numGuests = (int) str_replace('+', '', $guestCapacity);
                if ($isPlus) {
                    $q->where('maximum_guests', '>=', $numGuests);
                } else {
                    $q->where('maximum_guests', '<=', $numGuests);
                }
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



        if ($request->filled('search')) {
            $query->where('property_name', 'LIKE', '%' . $request->search . '%');
        }


        // if ($request->filled('date_availability')) {
        //     $query->whereHas('propertyAvailability', function ($q) use ($request) {
        //         $date = Carbon::parse($request->date_availability);
        //         $q->where('updated_at', '<=', $date);
        //     });
        // }

        //    if ($request->filled(['check_in', 'check_out'])) {
        //     $checkIn  = Carbon::parse($request->check_in);
        //     $checkOut = Carbon::parse($request->check_out);

        //     $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut) {
        //         $q->where('start_date', '<=', $checkIn)
        //           ->where('end_date', '>=', $checkOut)
        //           ->whereRaw("
        //               SUBSTRING(availability, DATEDIFF(?, start_date) + 1, DATEDIFF(?, ?) + 1) 
        //               = REPEAT('Y', DATEDIFF(?, ?) + 1)
        //           ", [
        //               $checkIn->format('Y-m-d'),   // start offset
        //               $checkOut->format('Y-m-d'),  // length till checkout
        //               $checkIn->format('Y-m-d'),   // length calc
        //               $checkOut->format('Y-m-d'), 
        //               $checkIn->format('Y-m-d')
        //           ]);
        //     });
        // }



        if ($request->filled('date_availability.start_date') && $request->filled('date_availability.end_date')) {
            $checkIn  = Carbon::parse($request->date_availability['start_date']);
            $checkOut = Carbon::parse($request->date_availability['end_date']);

            $query->whereHas('propertyAvailability.availabilityCalendars', function ($q) use ($checkIn, $checkOut) {
                $q->where('start_date', '<=', $checkIn)
                    ->where('end_date', '>=', $checkOut);
            });
        }


        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDoesntHave('bookings', function ($q) use ($request) {
                $q->where(function ($query) use ($request) {
                    $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date]);
                });
            });
        }

        $properties = $query->get();

        // Detect if any date filter is applied
        $hasDates = !empty($startDate) && !empty($endDate);
        $priceMap = collect();

        if ($hasDates) {
            try {
                // ISO formats for Hostaway
                $isoStart = Carbon::parse($startDate)->subDay()->format('Y-m-d\T19:00:00.000\Z');
                $isoEnd = Carbon::parse($endDate)->subDay()->format('Y-m-d\T19:00:00.000\Z');

                $bulkUrl = "https://api.hostaway.com/bookingEngines/booking.groupretreats.co.uk/listings";
                $bulkParams = [
                    'dates' => [$isoStart, $isoEnd],
                    // 'availabilityGuestNumber' => $numberOfGuests,
                    'calendarAvailabilityStartDate' => $startDate,
                    'calendarAvailabilityEndDate' => $endDate,
                    'limit' => 100,
                    'offset' => 0
                ];

                $hostawayResponse = Http::timeout(60)->get($bulkUrl, $bulkParams);
                $hostawayData = $hostawayResponse->json()['result'] ?? [];
                $priceMap = collect($hostawayData)->keyBy('id');

                // MANDATORY: Filter out properties where Hostaway price is null when dates are provided
                $properties = $properties->filter(function ($property) use ($priceMap) {
                    $hostawayListing = $priceMap[$property->property_id] ?? null;
                    
                    // Hide property if not found in Hostaway or price is null
                    if (!$hostawayListing) return false;
                    
                    $price = $hostawayListing['averageNightlyPrice'] ?? null;
                    return $price !== null;
                });
            } catch (\Exception $e) {
                Log::error("Hostaway API error in PropertyGridController: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'data' => [
                'properties' => $properties->values()->map(function ($property) use ($priceMap, $hasDates, $startDate, $endDate) {
                    if (is_string($property->amenities)) {
                        $property->amenities = json_decode($property->amenities, true);
                    }

                    $property->location_description = Str::limit($property->location_description, 50);
                    $listing = $property->content_listings->first();
                    $maxGuests = $listing ? $listing->maximum_guests : null;
                    $property->unsetRelation('content_listings');

                    $propertyArray = $property->toArray();
                    $orderedProperty = [];

                    foreach ($propertyArray as $key => $value) {
                        $orderedProperty[$key] = $value;
                        if ($key === 'area_unit') {
                            $orderedProperty['maximum_guests'] = $maxGuests;
                        }
                    }

                    if (!empty($property->custom_description)) {
                        $orderedProperty['description'] = $property->custom_description;
                    }

                    if (!array_key_exists('maximum_guests', $orderedProperty)) {
                        $orderedProperty['maximum_guests'] = $maxGuests;
                    }

                    // Pricing keys hamesha show honi chahiye jab dates searched hon
                    if ($hasDates) {
                        $hostawayListing = $priceMap[$property->property_id] ?? null;
                        $rawNightly = $hostawayListing['averageNightlyPrice'] ?? null;
                        $rawTotal = $hostawayListing['totalPriceForGivenPeriod'] ?? null;

                        // Fallback: calculate total if Hostaway returns null but nightly price exists
                        if ($rawTotal === null && $rawNightly !== null) {
                            $nights = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));
                            if ($nights <= 0) $nights = 1;
                            $rawTotal = $rawNightly * $nights;
                        }

                        // Use round() instead of (int) to match live site display
                        $orderedProperty['averageNightlyPrice'] = $rawNightly !== null ? round($rawNightly) : null;
                        $orderedProperty['totalPriceForGivenPeriod'] = $rawTotal !== null ? round($rawTotal) : null;
                    }

                    return $orderedProperty;
                }),
            ],
        ]);
    }
}