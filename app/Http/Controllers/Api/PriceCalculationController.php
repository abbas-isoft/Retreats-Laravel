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
    public function calculatePrice(Request $request, $propertyId)
{
    // Property ID from URL param
    $propertyId = intval($propertyId);

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

    // Step 3: Fetch prices
    $totalPrice = 0;
    $pricePerNight = 0;

    try {
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

            if (is_array($calendarData)) {
                $dailyPrices = [];

                foreach ($calendarData as $day) {
                    $dayDate = Carbon::parse($day['date'])->startOfDay();

                    if ($dayDate >= $checkIn && $dayDate < $checkOut) {
                        if (isset($day['price']) && is_numeric($day['price'])) {
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
        Log::debug("Price fetch error for property {$propertyId}: " . $e->getMessage());
    }

    // Fallback
    if ($totalPrice == 0) {
        try {
            $listingResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Content-Type' => 'application/json',
            ])->timeout(20)->get(
                "https://api.hostaway.com/v1/listings/{$propertyId}"
            );

            $listingData = $listingResponse->json();
            if (isset($listingData['result']['price'])) {
                $pricePerNight = (float) $listingData['result']['price'];
                $totalPrice = $pricePerNight * $nights;
            }
        } catch (\Exception $e) {}
    }

    return response()->json([
        'status' => true,
        'message' => 'Price calculated successfully',
        'data' => [
            'total_price' => round($totalPrice, 2),
            'per_day' => round($pricePerNight, 2),
            'nights' => $nights,
            'guest' => $guests,
            'dates' => $allDates,
        ],
    ]);
}

}