<?php

namespace App\Http\Controllers;

use App\Jobs\FetchCalendarJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
class CalendarController extends Controller
{
    public function fetchCalendar(Request $request)
{
    $request->validate([
        'listing_id' => 'required|integer',
        'startDate' => 'required|date',
        'endDate' => 'required|date|after_or_equal:startDate',
    ]);

    // Dispatch Job (Queue)
    FetchCalendarJob::dispatch(
        $request->listing_id,
        $request->startDate,
        $request->endDate
    );

    // API hit for immediate response (optional)
    $url = "https://api.hostaway.com/v1/listings/{$request->listing_id}/calendar?startDate={$request->startDate}&endDate={$request->endDate}";

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . env('HOSTAWAY_API_TOKEN'),
    ])->get($url);

    return response()->json([
        'status' => 'success',
        'message' => 'Calendar data fetched & syncing to DB',
        'data' => $response->json()
    ]);
}

}
