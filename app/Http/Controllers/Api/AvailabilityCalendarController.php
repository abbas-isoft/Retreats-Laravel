<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AvailabilityCalendarResource;
use App\Models\AvailabilityCalendar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailabilityCalendarController extends Controller
{
    public function index($propertyId): JsonResponse
    {
        $propertyContent = \App\Models\PropertyContent::where('property_id', $propertyId)->first();
        if (!$propertyContent) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $listingId = !empty($propertyContent->hostaway_id) ? $propertyContent->hostaway_id : $propertyContent->property_id;

        if (!$listingId) {
            return response()->json(['status' => false, 'message' => 'Hostaway Listing ID not found'], 404);
        }

        $startDate = now();
        $endDate = now()->addDays(730);

        $response = \Illuminate\Support\Facades\Http::get("https://booking-engine.hostaway.com/bookingEngines/booking.groupretreats.co.uk/listings/$listingId/calendar", [
            'startingDate' => $startDate->format('Y-m-d'),
        ]);

        if ($response->failed()) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch calendar from Hostaway'], 500);
        }

        $data = $response->json();
        $calendarResult = $data['result'] ?? [];
        
        $formattedData = [];
        foreach ($calendarResult as $day) {
             $isAvailable = ($day['isAvailable'] ?? 0) == 1 ? 1 : 0;
             $status = $day['status'] ?? 'unavailable';
             
             // If status is reserved, unavailable OR blocked, force isAvailable to 0
             if ($status === 'reserved' || $status === 'unavailable' || $status === 'blocked') {
                 $isAvailable = 0;
             }
             
             $formattedData[$day['date']] = [
                'id' => $day['id'] ?? null,
                'date' => $day['date'],
                'isAvailable' => $day['isAvailable'] ?? 0,
                'status' => $day['status'] ?? 'unavailable',
                'price' => $day['price'] ?? 0,
                'minimumStay' => $day['minimumStay'] ?? 1,
                'maximumStay' => $day['maximumStay'] ?? 365,
                'closedOnArrival' => $day['closedOnArrival'] ?? 0,
                'closedOnDeparture' => $day['closedOnDeparture'] ?? 0,
                'countReservedUnits' => $day['countReservedUnits'] ?? null,
                'countBlockedUnits' => $day['countBlockedUnits'] ?? null,
                'countPendingUnits' => $day['countPendingUnits'] ?? null,
                'countAvailableUnits' => $day['countAvailableUnits'] ?? null,
                'availableUnitsToSell' => $day['availableUnitsToSell'] ?? 0,
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $formattedData,
        ]);
    }

    public function allProperties(Request $request): JsonResponse
    {
        // 👉 CASE 1: Region select NA ho
        if (!$request->filled('region')) {

            $calendars = AvailabilityCalendar::all();

            if ($calendars->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No availability calendar data found',
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'All available properties fetched successfully',
                'data' => [
                    'propertyAvailability' => AvailabilityCalendarResource::collection($calendars),
                ]
            ]);
        }

        // 👉 CASE 2: Region select ho (existing logic – untouched)
        $regionName = $request->region;

        $regionId = DB::table('regions')
            ->where('name', $regionName)
            ->value('id');

        if (!$regionId) {
            return response()->json([
                'status' => false,
                'message' => 'Region not found',
            ]);
        }

        $propertyIds = DB::table('region_property')
            ->where('region_id', $regionId)
            ->pluck('property_id');

        if ($propertyIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No properties found in this region',
            ]);
        }

        $calendars = AvailabilityCalendar::whereIn('property_id', $propertyIds)->get();

        if ($calendars->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No availability calendar data found for this region',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Region wise availability fetched successfully',
            'data' => [
                'propertyAvailability' => AvailabilityCalendarResource::collection($calendars),
            ]
        ]);
    }
}
