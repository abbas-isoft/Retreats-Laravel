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
            'background_image' => 'nullable', // can be array, file, or string
            'background_video' => 'nullable', // video file or URL
            'is_active' => 'in:true,false,1,0',
        ]);

        $images = [];

        // ✅ Handle background images
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $imagePath = $file->store('images', 'public');
                $images[] = url('/storage/' . $imagePath);
            }
        } elseif (is_array($request->background_image)) {
            $images = $request->background_image;
        } elseif (is_string($request->background_image)) {
            $images[] = $request->background_image;
        }

        // ✅ Encode array to JSON
        $validated['background_image'] = json_encode($images);

        // ✅ Handle background video
        if ($request->hasFile('background_video')) {
            $videoPath = $request->file('background_video')->store('videos', 'public');
            $validated['background_video'] = url('/storage/' . $videoPath);
        } elseif (is_string($request->background_video)) {
            // For existing URL or frontend-provided link
            $validated['background_video'] = $request->background_video;
        }

        // ✅ Default value for is_active
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        // ✅ Create or update homepage content
        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        // ✅ Decode images before returning response
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully',
            'data'    => $content,
        ]);
    }
}
