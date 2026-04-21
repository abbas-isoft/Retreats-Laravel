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
        $section1 = $request->input('section_1');
        if (is_array($section1)) {
            $merge = [];

            if (!$request->filled('note') && isset($section1['button_title'])) {
                $merge['note'] = $section1['button_title'];
            }
            if (!$request->filled('description') && isset($section1['content'])) {
                $merge['description'] = $section1['content'];
            }
            if (!$request->filled('image_url') && isset($section1['image'])) {
                $merge['image_url'] = $section1['image'];
            }
            if (!$request->filled('cta_text') && isset($section1['cta_text'])) {
                $merge['cta_text'] = $section1['cta_text'];
            }

            if (!empty($merge)) {
                $request->merge($merge);
            }
        }

        $validated = $request->validate([
            'title'       => ['nullable', 'string', 'max:255'],
            'subtitle'    => ['nullable', 'string'],
            'image_url'   => ['nullable', 'string', 'max:2048'],
            'video_url'   => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
            'note'        => ['nullable', 'string', 'max:255'],
            'cta_text'    => ['nullable', 'string'],
            'active'      => ['nullable', 'integer'],
            'step_number' => ['nullable', 'integer'],
            'is_video'    => ['nullable', 'integer'],
        ]);

        if (!array_key_exists('cta_text', $validated) || $validated['cta_text'] === null) {
            $existingCta = BookingStep::whereNotNull('cta_text')->latest()->value('cta_text');
            if ($existingCta !== null) {
                $validated['cta_text'] = $existingCta;
            }
        }

        $record = null;

        if ($request->filled('id')) {
            $record = BookingStep::find($request->id);
        }

        if ($record) {
            $record->update($validated);
        } else {
            if (!array_key_exists('step_number', $validated) || $validated['step_number'] === null) {
                $maxStep = BookingStep::max('step_number');
                $validated['step_number'] = is_numeric($maxStep) ? ((int) $maxStep + 1) : 1;
            }
            if (!array_key_exists('active', $validated) || $validated['active'] === null) {
                $validated['active'] = 1;
            }
            if (!array_key_exists('is_video', $validated) || $validated['is_video'] === null) {
                $validated['is_video'] = 0;
            }
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
                'cta_text' => $step->cta_text ?? '',
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
