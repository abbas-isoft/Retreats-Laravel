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

    // Fetch calendars filtered by address_line if provided
    if ($addressLine) {
        $calendars = AvailabilityCalendar::whereHas('property.propertyContents', function ($query) use ($addressLine) {
            $query->where('address_line', 'like', "%{$addressLine}%");
        })->with('property.propertyContents')->get(); // eager load propertyContents
    } else {
        $calendars = AvailabilityCalendar::with('property.propertyContents')->get();
    }

    if ($calendars->isEmpty()) {
        return response()->json([
            'status' => false,
            'message' => 'No availability calendar data found for the given address.',
        ]);
    }

    // Map calendars to include address_line in response
    $calendarsWithAddress = $calendars->map(function ($calendar) {
        return [
            'id' => $calendar->id,
            'property_id' => $calendar->property_id,
            'start_date' => $calendar->start_date,
            'end_date' => $calendar->end_date,
            'availability' => $calendar->availability,
            'address_line' => $calendar->property->propertyContents->pluck('address_line'), // all related address lines

        ];
    });

    return response()->json([
        'status' => true,
        'message' => 'Availability calendars fetched successfully.',
        'data' => [
            'propertyAvailability' => $calendarsWithAddress,
        ]
    ]);
}


}
