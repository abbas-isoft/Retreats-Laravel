<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PriceConfiguration;
use App\Models\PriceLosEntry;
use App\Models\PriceFee;
use Carbon\Carbon;

class PropertyPriceController extends Controller
{
    public function getTotalPrice(Request $request, $propertyId)
    {
        $request->validate([
            'check_in'  => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests'    => 'required|integer|min:1'
        ]);

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights   = $checkIn->diffInDays($checkOut);
        $guests   = intval($request->guests);

        $property = Property::find($propertyId);
        if (!$property) {
            return response()->json([
                'status' => false,
                'message' => 'Property not found'
            ]);
        }

        $priceConfig = PriceConfiguration::where('property_id', $propertyId)->first();
        if (!$priceConfig) {
            return response()->json([
                'status' => false,
                'message' => 'Price configuration not found'
            ]);
        }


        $losEntries = PriceLosEntry::where('price_configuration_id', $priceConfig->id)->get();

        $fees = PriceFee::where('price_configuration_id', $priceConfig->id)->get();

        $perDayPrices = [];
        $totalPrice   = 0;

        for ($date = $checkIn->copy(); $date < $checkOut; $date->addDay()) {
            $dayPrice = 0;

            foreach ($losEntries as $entry) {
                $entryDate = Carbon::parse($entry->date);
                if ($entryDate->isSameDay($date)) {
                    $prices = json_decode($entry->length_of_stay_prices, true);
                    if (!empty($prices['priceLos'][0])) {
                        $priceValues = explode(',', $prices['priceLos'][0]);
                        $dayPrice = floatval($priceValues[0]); // Take first valid price
                    }
                    break;
                }
            }

            $dayPriceTotal = $dayPrice * $guests;

            $perDayPrices[] = [
                'date' => $date->toDateString(),
                'price_per_guest' => $dayPrice,
                'guests' => $guests,
                'total_for_day' => round($dayPriceTotal, 2),
            ];

            $totalPrice += $dayPriceTotal;
        }

        foreach ($fees as $fee) {
            if ($fee->type === 'Fixed') {
                $totalPrice += floatval($fee->price);
            } elseif ($fee->type === 'Percentage') {
                $totalPrice += ($totalPrice * floatval($fee->price) / 100);
            }
        }

        if ($priceConfig->deposit_type === 'Percentage') {
            $depositAmount = $totalPrice * ($priceConfig->deposit_value / 100);
        } else {
            $depositAmount = floatval($priceConfig->deposit_value);
        }

        $balanceDue = $totalPrice - $depositAmount;

        return response()->json([
            'status' => true,
            'property_id' => $propertyId,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests' => $guests,
            'nights' => $nights,
            'per_day_prices' => $perDayPrices,
            'total_price' => round($totalPrice, 2),
            'deposit_amount' => round($depositAmount, 2),
            'balance_due' => round($balanceDue, 2),
        ]);
    }
}
