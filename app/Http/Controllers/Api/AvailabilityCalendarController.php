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
        $calendars = AvailabilityCalendar::where('property_id', $propertyId)->get();

        if ($calendars->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No availability calendar data found for this property.',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => AvailabilityCalendar::where('property_id', $propertyId)->first(),
        ]);
    }

    public function allProperties(Request $request): JsonResponse
{
    $addressLine = $request->query('address_line');

    if ($addressLine) {
        $calendars = AvailabilityCalendar::whereHas('property.propertyContents', function ($query) use ($addressLine) {
            $query->where('address_line', 'like', "%{$addressLine}%");
        })->get();
    } else {
        $calendars = AvailabilityCalendar::all();
    }

    if ($calendars->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No availability calendar data found for the given address.',
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Availability calendars fetched successfully.',
        'data' => [
            'propertyAvailability' => AvailabilityCalendarResource::collection($calendars),
        ]
    ]);
}


}
