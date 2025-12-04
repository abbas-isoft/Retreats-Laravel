<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\AvailabilityCalendar;

class PriceCalculationController extends Controller
{
    public function calculatePrice(Request $request)
    {
        $propertyId = $request->property_id;
        $guests = $request->guests ?? 1; // optional guests param
        $checkIn = Carbon::parse(trim($request->check_in))->startOfDay();
        $checkOut = Carbon::parse(trim($request->check_out))->startOfDay();

        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < 1) {
            return response()->json([
                'status' => false,
                'message' => 'Check-out must be after Check-in.',
            ]);
        }

        // Step 1: Fetch property listing from Hostaway API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.hostaway.token'),
            'Content-Type' => 'application/json',
        ])->get("https://api.hostaway.com/v1/listings/{$propertyId}");

        $data = $response->json();
        if (!isset($data['result']) || !$data['result']) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found in Hostaway API.',
                'response' => $data
            ]);
        }

        $listing = $data['result'];

        // Step 2: Get price per night
        $pricePerNight = $listing['price'] ?? 0;
        $totalPrice = $pricePerNight * $nights;

        // Step 3: Check availability calendar
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

        // Step 4: Check each date availability
        $allDates = [];
        $currentDate = $checkIn->copy();
        $available = true;
        while ($currentDate < $checkOut) {
            $dayIndex = $calendarStart->diffInDays($currentDate);
            $status = $availability[$dayIndex - 1] ?? 'N';
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

        // ✅ Step 5: Return response exactly like your example
        return response()->json([
            'status' => true,
            'message' => 'Price calculated successfully',
            'data' => [
                'total_price' => $totalPrice,
                'per_day' => round($pricePerNight, 2),
                'nights' => $nights,
                'guest' => $guests,
                'dates' => $allDates,
            ],
        ]);
    }
}
