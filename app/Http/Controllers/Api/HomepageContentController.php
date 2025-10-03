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
    'background_image' => 'nullable', // 👈 remove "string" or "image"
]);

if ($request->hasFile('background_image')) {
    // Agar file upload ho rahi hai to yeh chalega
    $imagePath = $request->file('background_image')->store('images', 'public');
    $validated['background_image'] = '/storage/' . $imagePath;
} elseif ($request->background_image) {
    // Agar sirf string path aayi hai to direct use kar lo
    $validated['background_image'] = $request->background_image;
}
        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        return response()->json([
            'status' => true,
            'message' => 'Homepage content saved successfully',
            'data' => new HomepageContentResource($content),
        ]);
    }
}
