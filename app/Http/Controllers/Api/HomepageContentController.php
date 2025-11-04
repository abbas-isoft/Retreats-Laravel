<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use App\Http\Resources\HomepageContentResource;
use Illuminate\Support\Facades\Log;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\Image\Enums\Fit;

class HomepageContentController extends Controller
{
    public function index()
{
    $content = HomepageContent::first();

    if ($content) {
        $content->background_image = is_string($content->background_image)
            ? json_decode($content->background_image, true)
            : ($content->background_image ?? []);
    }

    return response()->json([
        'status' => true,
        'data'   => new HomepageContentResource($content),
    ]);
}

    public function store(Request $request)
{
    ini_set('memory_limit', '2048M');
    set_time_limit(300);

    $validated = $request->validate([
        'title'            => 'required|string',
        'subtitle'         => 'required|string',
        'background_image' => 'nullable',
        'background_video' => 'nullable',
        'is_video'         => 'in:true,false,1,0',
    ]);

    $images = [];

    if ($request->hasFile('background_image')) {
        foreach ($request->file('background_image') as $file) {

            $allowedExtensions = ['jpg', 'jpeg', 'png'];
            $extension = strtolower($file->getClientOriginalExtension());

            // Validate type
            if (!in_array($extension, $allowedExtensions)) {
                continue; // Skip non-image type
            }

            // Dynamic WebP filename
            $webpName = uniqid('img_') . '.webp';
            $savePath = storage_path('app/public/images/' . $webpName);
            $imagePath = $file->getRealPath();

            try {
                $src = false;
                switch ($extension) {
                    case 'jpeg':
                    case 'jpg':
                        $src = @imagecreatefromjpeg($imagePath);
                        break;
                    case 'png':
                        $src = @imagecreatefrompng($imagePath);
                        if ($src) {
                            imagepalettetotruecolor($src); // ✅ True color
                            imagealphablending($src, true);
                            imagesavealpha($src, true);
                        }
                        break;
                }

                if ($src) {
                    $width = imagesx($src);
                    $height = imagesy($src);
                    $maxWidth = 1920;
                    $maxHeight = 1080;

                    // Resize if too large
                    if ($width > $maxWidth || $height > $maxHeight) {
                        $ratio = min($maxWidth / $width, $maxHeight / $height);
                        $newWidth = (int)($width * $ratio);
                        $newHeight = (int)($height * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                        imagedestroy($src);
                        $src = $resized;
                    }

                    // ✅ Save WebP
                    if (!imagewebp($src, $savePath, 75)) {
                        throw new \Exception('GD WebP conversion failed.');
                    }

                    imagedestroy($src);
                } else {
                    // ⚡ Fallback to Spatie Conversion
                    Image::load($imagePath)
                        ->fit(Fit::Max, 1920, 1080)
                        ->format('webp')
                        ->quality(80)
                        ->save($savePath);
                }

                // ✅ Optimize (small size, great quality)
                $optimizerChain = OptimizerChainFactory::create();
                $optimizerChain->optimize($savePath);

                $images[] = url('/storage/images/' . $webpName);

            } catch (\Throwable $e) {
                Log::warning('⚠️ WebP Conversion Failed: ' . $e->getMessage());

                // Last fallback: save original as-is
                $storedPath = $file->storeAs('images', $webpName, 'public');
                $images[] = url('/storage/' . $storedPath);
            }
        }

        // Save image array as JSON
        $validated['background_image'] = json_encode($images, JSON_UNESCAPED_SLASHES);
    }

    // ✅ Video handling unchanged
    if ($request->hasFile('background_video')) {
        $videoPath = $request->file('background_video')->store('videos', 'public');
        $validated['background_video'] = url('/storage/' . $videoPath);
    } elseif (is_string($request->background_video)) {
        $validated['background_video'] = $request->background_video;
    }

    $validated['is_video'] = $request->boolean('is_video', false);

    $content = HomepageContent::updateOrCreate(['id' => 1], $validated);
    $content->background_image = is_string($content->background_image)
    ? json_decode($content->background_image, true)
    : ($content->background_image ?? []);

    return response()->json([
        'status'  => true,
        'message' => 'Homepage content saved successfully (Auto WebP + Resize)',
        'data'    => $content,
    ]);
}


    // 🆕 Added uploadImage() method with Spatie optimization
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:8192', // Max 8MB
        ]);

        $image = $request->file('image');
        $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $path = storage_path('app/public/uploads/');

        // Create directories if not exist
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        // Convert to WebP and optimize
        $webpFile = $path . $filename . '.webp';


        // Resize and convert to WebP
        Image::load($image->getRealPath())
            ->fit(Fit::Max, 2000, 2000) // ✅ replaces width() + height()
            ->format('webp')
            ->quality(85)
            ->save($webpFile);

        // Optimize final image size
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($webpFile);

        // Store publicly accessible file path (if using storage:link)
        $publicUrl = asset('storage/uploads/' . basename($webpFile));

        return response()->json([
            'success' => true,
            'url' => $publicUrl,
        ]);
    }
}
