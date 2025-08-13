<?php

namespace App\Http\Controllers\Api;

use App\Models\AboutUs;
use App\Http\Controllers\Controller;
use App\Http\Resources\AboutUsResource;
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
}
