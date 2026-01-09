<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityCalendarResource;
use App\Models\AvailabilityCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityCalendarController extends Controller
{

    public function index($propertyId): JsonResponse
    {
        $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'No Hostaway availability calendar found for this property.',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => new AvailabilityCalendarResource($calendar),
        ]);
    }
    public function allProperties(Request $request): JsonResponse
    {
        $addressLine = $request->query('address_line');

        if ($addressLine) {
            $calendars = AvailabilityCalendar::whereHas('property.propertyContents', function ($query) use ($addressLine) {
                $query->whereNotNull('hostaway_id')
                    ->where('hostaway_id', '!=', 0)
                    ->where('address_line', 'like', "%{$addressLine}%");
            })->get();
        } else {
            $calendars = AvailabilityCalendar::whereHas('property.propertyContents', function ($query) {
                $query->whereNotNull('hostaway_id')
                    ->where('hostaway_id', '!=', 0);
            })->get();
        }

        if ($calendars->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No Hostaway availability calendar data found.',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Hostaway availability calendars fetched successfully.',
            'data' => [
                'propertyAvailability' => AvailabilityCalendarResource::collection($calendars),
            ]
        ]);
    }
}
