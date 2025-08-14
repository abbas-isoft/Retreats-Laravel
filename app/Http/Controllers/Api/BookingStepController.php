<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingStep;
use Illuminate\Http\Request;
use App\Http\Resources\BookingStepResource;

class BookingStepController extends Controller
{
    // GET /api/booking-steps
    public function show()
{
    $data = BookingStep::latest()->first();

    if (!$data) {
        return response()->json([
            'title'       => null,
            'subtitle'    => null,
            'image_url'   => null,
            'video_url'   => null,
            'description' => null,
            'note'        => null,
        ]);
    }

    return new BookingStepResource($data);
}

// POST
public function store(Request $request)
{
    $validated = $request->validate([
        'title'       => ['nullable','string','max:255'],
        'subtitle'    => ['nullable','string'],
        'image_url'   => ['nullable','string','max:2048'],
        'video_url'   => ['nullable','string','max:2048'],
        'description' => ['nullable','string'],
        'note'        => ['nullable','string','max:255'],
    ]);

    $record = BookingStep::first();

    if ($record) {
        $record->update($validated);
    } else {
        $record = BookingStep::create($validated);
    }

    return response()->json([
        'message' => 'Booking steps saved successfully',
        'data'    => new BookingStepResource($record),
    ], 201);
}
}
