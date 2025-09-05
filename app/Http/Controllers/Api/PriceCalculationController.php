<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PriceLosEntry;
use Carbon\Carbon;
use App\Models\PriceConfiguration;

class PriceCalculationController extends Controller
{
    public function calculatePrice(Request $request)
    {
        $propertyId = $request->property_id;
        $checkIn = Carbon::parse(trim($request->check_in))->startOfDay();
        $checkOut = Carbon::parse(trim($request->check_out))->startOfDay();

        $nights = $checkIn->diffInDays($checkOut); // absolute difference

        // if ($nights < 1) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => 'Check-out must be after Check-in.',
        //         'check_in' => $checkIn->toDateString(),
        //         'check_out' => $checkOut->toDateString(),
        //         'diff' => $nights
        //     ]);
        // }

        // Step 1: propertyId se price_configuration_id nikaalo
        $config = PriceConfiguration::where('property_id', $propertyId)->first();

        if (!$config) {
            return response()->json([
                'status' => false,
                'message' => 'Property configuration not found.'
            ]);
        }

        // Step 2: LOS entry nikaalo check-in date ke liye
        $entry = PriceLosEntry::where('price_configuration_id', $config->id)
            ->where('date', $checkIn->toDateString())
            ->first();

        if (!$entry) {
            return response()->json([
                'status' => false,
                'message' => 'No pricing data found for this date'
            ]);
        }

        // Step 3: string ko array me convert karo
        $prices = explode(',', trim($entry->length_of_stay_prices, '"'));

        $guests = $prices[1] ?? 0;
        // Step 4: Remove first two elements
        $prices = array_slice($prices, 2);
        $price = $prices[$nights - 1] ?? 0;

        // $prices = array_values($prices);
        // // $prices
        // $inday = Carbon::parse($checkIn)->day - 1;
        // $outday = Carbon::parse($checkOut)->day - 2;
        // $length = $outday - $inday ;
        // $selectedPrices = array_slice($prices,  $inday, $length);

        // foreach ($selectedPrices as $p) {
        //     if ($p == 0) {
        //         return response()->json([
        //             'status' => false,
        //             'message' => 'Property Not Available for the selected dates',
        //             'nights' => $nights,
        //              'guest' => $guests,
        //              'inday' => $inday,
        //              'length' => $length,
        //             'price' => $prices,
        //         ]);
        //     }
        // }

        if ($price == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Property Not Available for ' . $nights . ' nights',
                'nights' => $nights,
                'price' => $prices,
            ]);
        }
        return response()->json([
            'status' => true,
            'total_price' => $price,
            'per_day' => round($price / $nights, 2),
            'nights' => $nights,
            'guest' => $guests,
            'price' => $prices,
        ]);
    }
}
