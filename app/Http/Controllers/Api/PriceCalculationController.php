<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PriceLosEntry;
use Carbon\Carbon;
use App\Models\PriceConfiguration;
use App\Models\AvailabilityCalendar;
use App\Http\Controllers\Api\AvailabilityCalendarController;
class PriceCalculationController extends Controller
{
    public function calculatePrice(Request $request)
{
    $propertyId = $request->property_id;
    $checkIn = Carbon::parse(trim($request->check_in))->startOfDay();
    $checkOut = Carbon::parse(trim($request->check_out))->startOfDay();

    $nights = $checkIn->diffInDays($checkOut);

    if ($nights < 1) {
        return response()->json([
            'status' => false,
            'message' => 'Check-out must be after Check-in.',
        ]);
    }

    // Step 1: get property config
    $config = PriceConfiguration::where('property_id', $propertyId)->first();
    if (!$config) {
        return response()->json([
            'status' => false,
            'message' => 'Property configuration not found.'
        ]);
    }

    // Step 2: get LOS entry for pricing
    $entry = PriceLosEntry::where('price_configuration_id', $config->id)
        ->where('date', $checkIn->toDateString())
        ->first();

    if (!$entry) {
        return response()->json([
            'status' => false,
            'message' => 'No pricing data found for this date'
        ]);
    }

    // Step 3: get calendar availability
    $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();
    if (!$calendar) {
        return response()->json([
            'status' => false,
            'message' => 'No availability calendar found.'
        ]);
    }

    $availability = $calendar->availability; // "NYYNNYY..." string
    $calendarStart = Carbon::parse($calendar->start_date)->startOfDay();
    $calendarEnd = Carbon::parse($calendar->end_date)->startOfDay();

    // Step 4: Make sure requested range is within calendar
    if ($checkIn < $calendarStart || $checkOut > $calendarEnd) {
        return response()->json([
            'status' => false,
            'message' => 'Selected dates are outside availability calendar.'
        ]);
    }

    // Step 5: check each date in the requested range
    $allDates = [];
    $currentDate = $checkIn->copy();
    $available = true;

    while ($currentDate < $checkOut) {
        $dayIndex = $calendarStart->diffInDays($currentDate); // position in string
        $status = $availability[$dayIndex -1] ?? 'N'; // default N if out of range
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
            'message' => 'Property Not Available for the selected dates',
            'unavailable_date' => $currentDate->toDateString(),
        ]);
    }

    // Step 6: calculate price
    $prices = explode(',', trim($entry->length_of_stay_prices, '"'));
    $guests = $prices[1] ?? 0;
    $prices = array_slice($prices, 2);
    $price = $prices[$nights - 1] ?? 0;

    if ($price == 0) {
        return response()->json([
            'status' => false,
            'price' => $prices,
            'available_dates' => $dayIndex,
            'c' => $currentDate, 
            'sa' => $calendarStart, 
            'message' => 'Property Not Available for ' . $nights . ' nights',
        ]);
    }

    return response()->json([
        'status' => true,
        'total_price' => $price,
        'per_day' => round($price / $nights, 2),
        'nights' => $nights,
        'guest' => $guests,
        'dates' => $allDates,
    ]);
}


    
}
