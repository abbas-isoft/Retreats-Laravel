<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use App\Http\Resources\HomepageContentResource;

class HomepageContentController extends Controller
{
    /**
     * GET API: Return homepage content
     */
    public function index()
    {
        $content = HomepageContent::first();

        if ($content && is_string($content->background_image)) {
            // Decode JSON if stored as string
            $content->background_image = json_decode($content->background_image, true);
        }

        return response()->json([
            'status' => true,
            'data'   => new HomepageContentResource($content),
        ]);
    }

    /**
     * POST API: Create or update homepage content
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string',
            'subtitle'         => 'required|string',
            'background_image' => 'nullable',
            'background_video' => 'nullable',
            'is_video'         => 'in:true,false,1,0',
        ]);

        $images = [];

        // ✅ Handle multiple image uploads
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $imagePath = $file->store('images', 'public');
                $images[] = url('/storage/' . $imagePath);
            }
        } elseif (is_array($request->background_image)) {
            $images = $request->background_image;
        } elseif (is_string($request->background_image)) {
            // Sometimes frontend sends single image as string
            $decoded = json_decode($request->background_image, true);
            $images = is_array($decoded) ? $decoded : [$request->background_image];
        }

        // ✅ Store as JSON only once
        $validated['background_image'] = json_encode($images, JSON_UNESCAPED_SLASHES);

        // ✅ Handle video upload or URL
        if ($request->hasFile('background_video')) {
            $videoPath = $request->file('background_video')->store('videos', 'public');
            $validated['background_video'] = url('/storage/' . $videoPath);
        } elseif (is_string($request->background_video)) {
            $validated['background_video'] = $request->background_video;
        }

        $validated['is_video'] = $request->boolean('is_video', false);

        // ✅ Create or update record
        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        // ✅ Decode for API response
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully',
            'data'    => $content,
        ]);
    }
}
