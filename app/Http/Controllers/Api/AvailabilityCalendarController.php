<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityCalendarResource;
use App\Models\AvailabilityCalendar;
use Illuminate\Http\JsonResponse;

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

    public function allProperties(): JsonResponse
{
    // Fetch all availability calendars
    $calendars = AvailabilityCalendar::all();

    if ($calendars->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No availability calendar data found for any property.',
        ]);
    }

    return response()->json([
        'status' => true,
        'propertyAvailability' => AvailabilityCalendarResource::collection($calendars),
    ]);
}

}
