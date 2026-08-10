<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AvailabilityCalendar;

class PriceCalculationController extends Controller
{
    public function calculatePrice(Request $request, $propertyId = null)
    {
        // Property ID from URL param OR request query
        $propertyId = $propertyId ?? intval($request->property_id ?? $request->query('property_id'));

        $guests = $request->guests ?? 1;
        $checkIn = Carbon::parse(trim($request->check_in))->startOfDay();
        $checkOut = Carbon::parse(trim($request->check_out))->startOfDay();

        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < 1) {
            return response()->json([
                'status' => false,
                'message' => 'Check-out must be after Check-in.',
            ]);
        }

        // Step 1: Check availability calendar
        $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'No availability calendar found.'
            ]);
        }

        $availability = $calendar->availability;
        $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
        $calendarEnd = Carbon::parse($calendar->end_date)->startOfDay();

        if ($checkIn < $calendarStart || $checkOut > $calendarEnd) {
            return response()->json([
                'status' => false,
                'message' => 'Selected dates are outside availability calendar.'
            ]);
        }

        // Step 2: Check each date
        $allDates = [];
        $currentDate = $checkIn->copy();
        $available = true;

        while ($currentDate < $checkOut) {
            $dayIndex = $calendarStart->diffInDays($currentDate);
            $status = $availability[$dayIndex] ?? 'N';

            if ($status === 'N') {
                $available = false;
                break;
            }

            $allDates[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        if (!$available) {
            return response()->json([
                'status' => false,
                'message' => 'Property not available for selected dates',
                'unavailable_date' => $currentDate->toDateString(),
            ]);
        }

        // Step 3: Fetch prices from Hostaway Booking Engine API
        $totalPrice = 0;
        $pricePerNight = 0;

        try {
            // Use Booking Engine logic to match live site exactly
            $isoStart = $checkIn->copy()->subDay()->format('Y-m-d\T19:00:00.000\Z');
            $isoEnd = $checkOut->copy()->subDay()->format('Y-m-d\T19:00:00.000\Z');

            $bulkUrl = "https://api.hostaway.com/bookingEngines/booking.groupretreats.co.uk/listings";
            $bulkParams = [
                'dates' => [$isoStart, $isoEnd],
                'availabilityGuestNumber' => $guests,
                'calendarAvailabilityStartDate' => $checkIn->format('Y-m-d'),
                'calendarAvailabilityEndDate' => $checkOut->format('Y-m-d'),
                'limit' => 100,
                'offset' => 0
            ];

            $hostawayResponse = Http::timeout(60)->get($bulkUrl, $bulkParams);
            $hostawayData = $hostawayResponse->json()['result'] ?? [];

            // Find the specific property in the results
            $listingData = collect($hostawayData)->firstWhere('id', $propertyId);

            if ($listingData) {
                $pricePerNight = $listingData['averageNightlyPrice'] ?? 0;
                $totalPrice = $listingData['totalPriceForGivenPeriod'] ?? 0;

                // The booking-engine list price does not include the listing's
                // length-of-stay discount. Hostaway stores the discounted
                // multiplier (for example, 0.85 means 15% off) on the listing.
                $weeklyDiscountMultiplier = data_get($listingData, 'listing.weeklyDiscount');

                if (
                    $nights >= 7 &&
                    is_numeric($weeklyDiscountMultiplier) &&
                    $weeklyDiscountMultiplier > 0 &&
                    $weeklyDiscountMultiplier < 1
                ) {
                    $totalPrice *= (float) $weeklyDiscountMultiplier;
                }
            }

            // Fallback to Calendar API if Booking Engine doesn't return data
            if ($totalPrice == 0) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                    'Content-Type' => 'application/json',
                ])->timeout(30)->get(
                    "https://api.hostaway.com/v1/listings/{$propertyId}/calendar",
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
            }
        } catch (\Exception $e) {
            Log::debug("Price fetch error for property {$propertyId}: " . $e->getMessage());
        }

        // Fallback: If no price found from calendar, try listing endpoint
        if ($totalPrice == 0) {
            try {
                $listingResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                    'Content-Type' => 'application/json',
                ])->timeout(20)->get(
                    "https://api.hostaway.com/v1/listings/{$propertyId}"
                );

                $listingData = $listingResponse->json();
                if (isset($listingData['result']['price']) && is_numeric($listingData['result']['price'])) {
                    $pricePerNight = (float) $listingData['result']['price'];
                    $totalPrice = $pricePerNight * $nights;
                }
            } catch (\Exception $e) {
                Log::debug("Fallback price fetch error for property {$propertyId}: " . $e->getMessage());
            }
        }

        try {
            // Fetch all listings to find the one with the matching propertyId
            $listingResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Content-Type' => 'application/json',
            ])->timeout(20)->get("https://api.hostaway.com/v1/listings");

            $listingData = $listingResponse->json();
            $bookingFeePercent = 0;

            if (isset($listingData['result']) && is_array($listingData['result'])) {
                // Filter the listings to find our specific property
                $specificListing = collect($listingData['result'])->firstWhere('id', $propertyId);

                if ($specificListing && isset($specificListing['listingFeeSetting'])) {
                    foreach ($specificListing['listingFeeSetting'] as $fee) {
                        // Check for Booking Fee and if it's a percentage type
                        if (
                            isset($fee['feeTitle']) && $fee['feeTitle'] === 'Booking Fee' &&
                            isset($fee['amountType']) && $fee['amountType'] === 'percent'
                        ) {
                            $bookingFeePercent = floatval($fee['amount']);
                            break;
                        }
                    }
                }
            }

            // Add booking fee **only if Booking Fee exists for this property**
            if ($bookingFeePercent > 0) {
                $totalPrice += ($totalPrice * $bookingFeePercent) / 100;
                Log::info("Added {$bookingFeePercent}% Booking Fee for property {$propertyId}");
            }
        } catch (\Exception $e) {
            Log::debug("Booking fee fetch error: " . $e->getMessage());
        }

        // Return response with updated total price only
        return response()->json([
            'status' => true,
            'message' => 'Price calculated successfully',
            'data' => [
                'total_price' => ($totalPrice),
                'per_day' => round($totalPrice / $nights),
                'nights' => $nights,
                'guest' => $guests,
                'dates' => $allDates,
            ],
        ]);
    }
}
