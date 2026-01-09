<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoUploadController extends Controller
{
    /**
     * Upload a video and return its public URL (no size limit).
     */
    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/x-flv,video/3gpp,video/avi,video/webm',
        ]);

        $path = $request->file('video')->store('videos', 'public');


        $url = asset('storage/' . $path);

        return response()->json([
            'status' => true,
            'message' => 'Video uploaded successfully',
            'video_url' => $url,
        ]);
    }
}
