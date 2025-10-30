<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use App\Http\Resources\HomepageContentResource;
use Illuminate\Support\Facades\Storage;

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
        // 🧠 Increase memory and execution limits temporarily
        ini_set('memory_limit', '1024M');  // 1GB memory
        set_time_limit(120);               // 2 minutes max execution

        $validated = $request->validate([
            'title'            => 'required|string',
            'subtitle'         => 'required|string',
            'background_image' => 'nullable',
            'background_video' => 'nullable',
            'is_video'         => 'in:true,false,1,0',
        ]);

        $images = [];

        // ✅ Handle multiple image uploads (optimized & fast)
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                $imagePath = $file->getRealPath();

                // 🧠 Load image depending on type
                switch ($extension) {
                    case 'jpeg':
                    case 'jpg':
                        $src = @imagecreatefromjpeg($imagePath);
                        break;
                    case 'png':
                        $src = @imagecreatefrompng($imagePath);
                        imagepalettetotruecolor($src);
                        imagealphablending($src, true);
                        imagesavealpha($src, true);
                        break;
                    case 'gif':
                        $src = @imagecreatefromgif($imagePath);
                        break;
                    default:
                        $storedPath = $file->store('images', 'public');
                        $images[] = url('/storage/' . $storedPath);
                        continue 2;
                }

                if (!$src) {
                    // Fallback if image couldn't load
                    $storedPath = $file->store('images', 'public');
                    $images[] = url('/storage/' . $storedPath);
                    continue;
                }

                // 🧩 Get original dimensions
                $width = imagesx($src);
                $height = imagesy($src);

                // ✅ Auto resize only if large (maintains aspect ratio)
                $maxWidth = 1920;
                $maxHeight = 1080;

                if ($width > $maxWidth || $height > $maxHeight) {
                    $ratio = min($maxWidth / $width, $maxHeight / $height);
                    $newWidth = (int)($width * $ratio);
                    $newHeight = (int)($height * $ratio);

                    $tmp = imagecreatetruecolor($newWidth, $newHeight);
                    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    imagedestroy($src);
                    $src = $tmp;
                }

                // ✅ Convert and compress to WebP (medium compression)
                $webpName = uniqid('img_') . '.webp';
                $savePath = storage_path('app/public/images/' . $webpName);

                // 🧠 Faster compression, low memory
                imagewebp($src, $savePath, 60);
                imagedestroy($src);

                // ✅ Add final image URL
                $images[] = url('/storage/images/' . $webpName);

                // 🧹 Free up memory after each image
                gc_collect_cycles();
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
            'message' => 'Homepage content saved successfully (fast optimized & resized)',
            'data'    => $content,
        ]);
    }
}
