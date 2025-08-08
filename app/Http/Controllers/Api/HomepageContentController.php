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
            'background_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('background_image')) {
            $imagePath = $request->file('background_image')->store('images', 'public');
            $validated['background_image'] = '/storage/' . $imagePath;
        }

        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        return response()->json([
            'status' => true,
            'message' => 'Homepage content saved successfully',
            'data' => new HomepageContentResource($content),
        ]);
    }
}
