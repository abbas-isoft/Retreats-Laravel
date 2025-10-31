<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use App\Http\Resources\HomepageContentResource;

class HomepageContentController extends Controller
{
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

    public function store(Request $request)
    {
        // 🧠 Safe limits for better performance
        ini_set('memory_limit', '512M');
        set_time_limit(60);

        $validated = $request->validate([
            'title'            => 'required|string',
            'subtitle'         => 'required|string',
            'background_image' => 'nullable',
            'background_video' => 'nullable',
            'is_video'         => 'in:true,false,1,0',
        ]);

        $images = [];

        // ✅ Handle image uploads safely with optimization
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $imagePath = $file->getRealPath();

                switch ($extension) {
                    case 'jpeg':
                    case 'jpg':
                        $src = @imagecreatefromjpeg($imagePath);
                        break;
                    case 'png':
                        $src = @imagecreatefrompng($imagePath);
                        if ($src) {
                            imagepalettetotruecolor($src);
                            imagealphablending($src, true);
                            imagesavealpha($src, true);
                        }
                        break;
                    case 'gif':
                        $src = @imagecreatefromgif($imagePath);
                        break;
                    default:
                        // unsupported formats (e.g., webp already)
                        $storedPath = $file->store('images', 'public');
                        $images[] = url('/storage/' . $storedPath);
                        continue 2;
                }

                if (!$src) {
                    // Fallback: save original if cannot be optimized
                    $storedPath = $file->store('images', 'public');
                    $images[] = url('/storage/' . $storedPath);
                    continue;
                }

                // 🧩 Get original size
                $width = imagesx($src);
                $height = imagesy($src);

                // ✅ Resize only if larger than Full HD
                $maxWidth = 1280;
                $maxHeight = 720;

                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $resized = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($src);
                    $src = $resized;
                }

                // ✅ Compress & convert to WebP (medium-light size)
                $webpName = uniqid('img_') . '.webp';
                $savePath = storage_path('app/public/images/' . $webpName);

                // Lower quality = smaller size (still clear on frontend)
                imagewebp($src, $savePath, 50);

                // Clean memory
                imagedestroy($src);
                gc_collect_cycles();

                $images[] = url('/storage/images/' . $webpName);
            }
        } elseif (is_array($request->background_image)) {
            $images = $request->background_image;
        } elseif (is_string($request->background_image)) {
            $decoded = json_decode($request->background_image, true);
            $images = is_array($decoded) ? $decoded : [$request->background_image];
        }

        // ✅ Store image URLs
        $validated['background_image'] = json_encode($images, JSON_UNESCAPED_SLASHES);

        // ✅ Handle video upload or URL
        if ($request->hasFile('background_video')) {
            $videoPath = $request->file('background_video')->store('videos', 'public');
            $validated['background_video'] = url('/storage/' . $videoPath);
        } elseif (is_string($request->background_video)) {
            $validated['background_video'] = $request->background_video;
        }

        $validated['is_video'] = $request->boolean('is_video', false);

        // ✅ Save/Update record
        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully (optimized & lightweight)',
            'data'    => $content,
        ]);
    }
}
