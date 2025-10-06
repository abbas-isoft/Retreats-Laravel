<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    /**
     * Upload an image and return its public URL.
     */
    public function upload(Request $request)
    {
        // Validate file input
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        // Store the file in "public/images" directory
        $path = $request->file('image')->store('images', 'public');

        // Generate full public URL
        $url = asset('storage/' . $path);

        return response()->json([
            'status' => true,
            'message' => 'Image uploaded successfully',
            'image_url' => $url,
        ]);
    }
}
