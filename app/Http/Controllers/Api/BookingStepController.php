<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingStep;
use Illuminate\Http\Request;
use App\Http\Resources\BookingStepResource;

class BookingStepController extends Controller
{
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'video_url'   => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'note'        => ['nullable', 'string', 'max:255'],
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
        $steps = BookingStep::query()
            ->orderByRaw('CASE WHEN step_number IS NULL THEN 1 ELSE 0 END')
            ->orderBy('step_number')
            ->orderByDesc('id')
            ->get();

        $stepsArray = $steps->map(function ($step) {
            $imageUrl = $step->image_url;

            if ($imageUrl && str_starts_with($imageUrl, 'http')) {
                $finalImage = $imageUrl;
            } elseif ($imageUrl) {
                $finalImage = url('storage/images/' . $imageUrl);
            } else {
                $finalImage = null;
            }

            return [
                'id' => $step->id,
                'title' => $step->title ?? '',
                'subtitle' => $step->subtitle ?? '',
                'description' => $step->description ?? '',
                'note' => $step->note ?? '',
                'image_url' => $finalImage,
                'video_url' => $step->video_url ?? '',
                'step_number' => $step->step_number ?? null,
                'is_video' => $step->is_video ?? null,
                'active' => $step->active ?? null,
                'created_at' => $step->created_at,
                'updated_at' => $step->updated_at,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Booking Steps content fetched successfully.',
            'data' => [
                'steps' => $stepsArray,
            ],
        ]);
    }
}
