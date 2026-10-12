<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        $path = $request->file('image')->store('images', 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'status' => true,
            'message' => 'Image uploaded successfully',
            'image_url' => $url,
        ]);
    }
}
