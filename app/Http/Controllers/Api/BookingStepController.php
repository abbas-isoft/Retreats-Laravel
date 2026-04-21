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
        return $this->getBookingSteps();
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
        $header = BookingStep::query()
            ->where(function ($q) {
                $q->whereNotNull('title')
                    ->orWhereNotNull('subtitle')
                    ->orWhereNotNull('video_url');
            })
            ->orderByDesc('id')
            ->first();

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
                'description' => $step->description ?? '',
                'tagline' => $step->cta_text ?? '',
                'button_title' => $step->note ?? '',
                'image' => $finalImage,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Booking Steps content fetched successfully.',
            'data' => [
                'title' => $header?->title ?? '',
                'subtitle' => $header?->subtitle ?? '',
                'video_url' => $header?->video_url ?? '',
                'steps' => $stepsArray,
            ],
        ]);
    }
}
