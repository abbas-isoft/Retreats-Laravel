<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IconUploadController extends Controller
{
    /**
     * Upload an icon and return its public URL (prevent overwriting if exists).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        $file = $request->file('image');
        $fileName = $file->getClientOriginalName();
        $uploadPath = public_path('images/' . $fileName);

        if (file_exists($uploadPath)) {
            return response()->json([
                'status' => false,
                'message' => 'Icon already exists',
                'image_url' => asset('images/' . $fileName),
            ], 409);
        }

        $file->move(public_path('images'), $fileName);
        $url = asset('images/' . $fileName);

        return response()->json([
            'status' => true,
            'message' => 'Icon uploaded successfully',
            'image_url' => $url,
        ]);
    }
}
