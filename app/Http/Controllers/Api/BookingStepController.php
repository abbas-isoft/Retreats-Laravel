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

public function getBookingSteps()
{
    $bookingStep = BookingStep::latest()->first();

    if (!$bookingStep) {
        return response()->json([
            'status' => false,
            'message' => 'Booking Steps content not found.',
        ], 404);
    }

    // ✅ Image URL handling
    $imageUrl = $bookingStep->image_url;

    if ($imageUrl && str_starts_with($imageUrl, 'http')) {
        $finalImage = $imageUrl;
    } elseif ($imageUrl) {
        $finalImage = url('storage/images/' . $imageUrl);
    } else {
        $finalImage = url('images/image.jpg');
    }

    // ✅ Section 1
    $section1 = [
        'title'        => $bookingStep->title ?? '',
        'content'      => $bookingStep->description ?? '',
        'button_title' => $bookingStep->button_text ?? 'Enquire Now',
        'image'        => $finalImage,
    ];

    // Section 2
    // $section2 = [
    //     'title' => $bookingStep->subtitle ?? '',
    //     'content' => $bookingStep->note ?? '',
    //     'button_title' => $bookingStep->button_text ?? 'Enquire Now',
    //     'image' => $bookingStep->image_url 
    //         ? url('images/'.$bookingStep->image_url) 
    //         : url('images/image.jpg'),
    // ];

    return response()->json([
        'status' => true,
        'message' => 'Booking Steps content fetched successfully.',
        'data' => [
            'title' => $bookingStep->title ?? '',
            'subtitle' => $bookingStep->subtitle ?? '',
            'video_url' => $bookingStep->video_url ?? '',
            'section_1' => $section1,
            // 'section_2' => $section2,
        ],
    ]);
}

}
