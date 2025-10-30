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

        if ($content && is_string($content->background_image)) {
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

        // ✅ Handle multiple image uploads (auto convert to optimized .webp)
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $imagePath = $file->getRealPath();

                // Create image resource
                switch ($extension) {
                    case 'jpeg':
                    case 'jpg':
                        $image = imagecreatefromjpeg($imagePath);
                        break;
                    case 'png':
                        $image = imagecreatefrompng($imagePath);
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                        break;
                    case 'gif':
                        $image = imagecreatefromgif($imagePath);
                        break;
                    default:
                        $storedPath = $file->store('images', 'public');
                        $images[] = url('/storage/' . $storedPath);
                        continue 2;
                }

                // ✅ Resize large images to max 1920px width (maintain aspect ratio)
                $width = imagesx($image);
                $height = imagesy($image);
                $maxWidth = 1920;
                $maxHeight = 1080;

                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($image);
                    $image = $resized;
                }

                // ✅ Compress and convert to WebP (strong compression)
                $webpName = uniqid() . '.webp';
                $savePath = storage_path('app/public/images/' . $webpName);

                // Compression: 65% (good balance between size and quality)
                imagewebp($image, $savePath, 65);
                imagedestroy($image);

                // ✅ Add optimized image URL
                $images[] = url('/storage/images/' . $webpName);
            }
        } elseif (is_array($request->background_image)) {
            $images = $request->background_image;
        } elseif (is_string($request->background_image)) {
            $decoded = json_decode($request->background_image, true);
            $images = is_array($decoded) ? $decoded : [$request->background_image];
        }

        // ✅ Store JSON
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
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully (optimized & resized)',
            'data'    => $content,
        ]);
    }
}
