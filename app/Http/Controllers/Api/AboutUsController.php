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
    // GET: Fetch About Us Content
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

    // POST: Store or Update About Us Content
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|string', // Assuming you're storing image URLs
        ]);

        // If exists, update. Else, create new.
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

        if (!$about) {
            return response()->json([
                'status' => false,
                'message' => 'About Us content not found.',
            ], 404);
        }

        $guide = BookingGuide::first();

        if (!$guide) {
            return response()->json(['message' => 'No content found'], 404);
        }

        $section = HospitalitySection::first(); // always only one record

        return response()->json([
            'status' => true,
            'message' => 'About Us content fetched successfully.',
            // 'about' => new AboutUsResource($about),
            // 'guide' => new BookingGuideResource($guide),
            // 'section' => $section,
            'data' => [
                'section-1' => new AboutUsResource($about),
                'section-2' => new BookingGuideResource($guide),
                // 'section' => $section,
            ],
        ]);
    }
}
