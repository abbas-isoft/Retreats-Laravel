<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use App\Http\Resources\HomepageContentResource;
use Illuminate\Support\Facades\Storage;

class HomepageContentController extends Controller
{
    /**
     * GET API: Return homepage content
     */
    public function index()
    {
        $content = HomepageContent::first();

        return response()->json([
            'status' => true,
            'data' => new HomepageContentResource($content),
        ]);
    }

    /**
     * POST API: Create or update homepage content
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'subtitle' => 'required|string',
            'background_image' => 'nullable', // can be array or file
        ]);

        $images = [];

        // ✅ If multiple files uploaded
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $imagePath = $file->store('images', 'public');
                $images[] = url('/storage/' . $imagePath);
            }
        }
        // ✅ If already have array of image URLs (from frontend)
        elseif (is_array($request->background_image)) {
            $images = $request->background_image;
        }
        // ✅ If single string (old behavior)
        elseif (is_string($request->background_image)) {
            $images[] = $request->background_image;
        }

        // ✅ Encode images array to store in DB
        $validated['background_image'] = json_encode($images);

        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        // ✅ Decode before sending response
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status' => true,
            'message' => 'Homepage content saved successfully',
            'data' => $content,
        ]);
    }
}
