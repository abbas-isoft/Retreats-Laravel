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
        $calendar = AvailabilityCalendar::where('property_id', $propertyId)->first();

        if (!$calendar) {
            return response()->json([
                'status' => false,
                'message' => 'No availability calendar found for this property.',
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => new AvailabilityCalendarResource($calendar),
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
