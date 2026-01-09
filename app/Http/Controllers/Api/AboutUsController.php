<?php

namespace App\Http\Controllers\Api;

use App\Models\AboutUs;
use App\Models\BookingGuide;
use App\Models\HospitalitySection;
use App\Http\Controllers\Controller;
use App\Http\Resources\AboutUsResource;
use App\Http\Resources\BookingGuideResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AboutUsController extends Controller
{
    public function show(): JsonResponse
    {
        $about = AboutUs::first();

        if (!$about) {
            return response()->json([
                'status' => false,
                'message' => 'About Us content not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About Us content fetched successfully.',
            'data' => new AboutUsResource($about),
        ]);
    }
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|string',
        ]);

        $about = AboutUs::first();

        if ($about) {
            $about->update($request->only(['title', 'description', 'image']));
            $message = 'About Us content updated successfully.';
        } else {
            $about = AboutUs::create($request->only(['title', 'description', 'image']));
            $message = 'About Us content created successfully.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => new AboutUsResource($about),
        ]);
    }

    public function GetAboutPage()
    {
        $about = AboutUs::first();
        $guide = BookingGuide::first();

        if (!$about && !$guide) {
            return response()->json([
                'status' => false,
                'message' => 'About Us content not found.',
            ], 404);
        }
        $getImageUrl = function ($image) {
            if (!$image) return url('images/default-about-us.jpg');
            if (preg_match('/^https?:\/\//', $image)) {
                return $image;
            }
            return url('storage/images/' . ltrim($image, '/'));
        };

        $section1 = $about ? [
            'title' => $about->title,
            'content' => $about->description,
            'button_title' => $about->button_text ?? 'Enquire Now',
            'image' => $getImageUrl($about->image),
        ] : null;

        $section2 = $guide ? [
            'title' => $guide->title ?? '',
            'content' => $guide->description ?? $guide->subtitle ?? '',
            'button_title' => $guide->button_text ?? 'Enquire Now',
            'image' => $getImageUrl($guide->image ?? null),
        ] : null;

        return response()->json([
            'status' => true,
            'message' => 'About Us content fetched successfully.',
            'data' => [
                'title' => $about->title ?? '',
                'subtitle' => $about->subtitle ?? '',
                'video_url' => $guide->video_url ?? '',
                'section_1' => $section1,
                'section_2' => $section2,
            ],
        ]);
    }
}
