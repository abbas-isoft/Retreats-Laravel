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
    public function calculatePrice(Request $request)
    {
        //-------------------------------------------
        // 1) VALIDATION
        //-------------------------------------------
        $request->validate([
            'property_id' => 'required|integer',
            'check_in'    => 'required|date',
            'check_out'   => 'required|date|after:check_in',
            'guests'      => 'nullable|integer|min:1',
        ]);

        $propertyId = intval($request->property_id);
        $guests     = $request->guests ?? 1;
        $checkIn    = Carbon::parse(trim($request->check_in))->startOfDay();
        $checkOut   = Carbon::parse(trim($request->check_out))->startOfDay();

        $nights = $checkIn->diffInDays($checkOut);

        //-------------------------------------------
        // 2) Check Availability Calendar
        //-------------------------------------------
        $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'property_id' => $propertyId,
                'message' => 'No availability calendar found.',
            ]);
        }

        $availability  = $calendar->availability;
        $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
        $calendarEnd   = Carbon::parse($calendar->end_date)->startOfDay();

        if ($checkIn < $calendarStart || $checkOut > $calendarEnd) {
            return response()->json([
                'status' => false,
                'message' => 'Selected dates are outside availability calendar.',
            ]);
        }

        //-------------------------------------------
        // 3) Check if all days are available
        //-------------------------------------------
        $allDates = [];
        $currentDate = $checkIn->copy();

        while ($currentDate < $checkOut) {

            $dayIndex = $calendarStart->diffInDays($currentDate);
            $status = $availability[$dayIndex] ?? 'N';

            if ($status === 'N') {
                return response()->json([
                    'status' => false,
                    'message' => 'Property not available for selected dates.',
                    'unavailable_date' => $currentDate->toDateString(),
                ]);
            }

            $allDates[] = $currentDate->toDateString();
            $currentDate->addDay();
        }

        //-------------------------------------------
        // 4) Fetch Hostaway DAILY Prices
        //-------------------------------------------
        $totalPrice     = 0;
        $pricePerNight  = 0;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                'Content-Type'  => 'application/json',
            ])
            ->timeout(30)
            ->get("https://api.hostaway.com/v1/listings/{$propertyId}/calendar", [
                'startDate' => $checkIn->format('Y-m-d'),
                'endDate'   => $checkOut->format('Y-m-d'),
            ]);

            $calendarData = $response->json()['result'] ?? null;

            if (is_array($calendarData)) {

                $dailyPrices = [];

                foreach ($calendarData as $day) {
                    $dayDate = Carbon::parse($day['date'])->startOfDay();

                    if ($dayDate >= $checkIn && $dayDate < $checkOut) {
                        if (!empty($day['price']) && is_numeric($day['price'])) {

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

        } catch (\Exception $e) {
            Log::warning("Hostaway price fetch error for property {$propertyId}: " . $e->getMessage());
        }

        //-------------------------------------------
        // 5) Fallback Price (Listing Base Price)
        //-------------------------------------------
        if ($totalPrice == 0) {

            try {

                $listingResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.hostaway.token'),
                    'Content-Type' => 'application/json',
                ])
                ->timeout(20)
                ->get("https://api.hostaway.com/v1/listings/{$propertyId}");

                $listingData = $listingResponse->json();

                if (isset($listingData['result']['price'])) {
                    $pricePerNight = (float) $listingData['result']['price'];
                    $totalPrice = $pricePerNight * $nights;
                }

            } catch (\Exception $e) {
                Log::warning("Base price fallback failed: " . $e->getMessage());
            }
        }

        //-------------------------------------------
        // 6) Final Response
        //-------------------------------------------
        return response()->json([
            'status'  => true,
            'message' => 'Price calculated successfully.',
            'data'    => [
                'total_price' => round($totalPrice, 2),
                'per_day'     => round($pricePerNight, 2),
                'nights'      => $nights,
                'guests'      => $guests,
                'dates'       => $allDates,
            ]
        ]);
    }
}
