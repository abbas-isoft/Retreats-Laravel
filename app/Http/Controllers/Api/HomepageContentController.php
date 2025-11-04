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
    ini_set('memory_limit', '2048M'); // Increase memory
    set_time_limit(300); // More time for large files

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
    $extension = strtolower($file->getClientOriginalExtension());
    $imagePath = $file->getRealPath();
    $webpName = uniqid('img_') . '.webp';
    $savePath = storage_path('app/public/images/' . $webpName);

    try {
        // ⚡ 1. Try GD for fast and safe resize
        $src = false;
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
                $src = false;
        }

        if ($src) {
            $width = imagesx($src);
            $height = imagesy($src);
            $maxWidth = 1920;
            $maxHeight = 1080;

            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio = min($maxWidth / $width, $maxHeight / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($src);
                $src = $resized;
            }

            // ✅ Convert and save as WebP
            if (function_exists('imagewebp') && imagewebp($src, $savePath, 75)) {
                throw new \Exception('GD WebP conversion failed.');
            }
            imagedestroy($src);
        } else {
            // ⚡ 2. Fallback to Spatie if GD failed
            Image::load($imagePath)
                ->fit(Fit::Max, 2000, 2000)
                ->format('webp')
                ->quality(80)
                ->save($savePath);
        }

        // ✅ 3. Optimize the WebP file
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($savePath);

        $images[] = url('/storage/images/' . $webpName);

    } catch (\Throwable $e) {
        Log::warning('⚠️ Conversion failed, fallback to manual WebP: ' . $e->getMessage());

        try {
            // 🧩 Last fallback — always WebP (no matter what)
            $originalData = file_get_contents($imagePath);
            $img = @imagecreatefromstring($originalData);
            if ($img) {
                imagewebp($img, $savePath, 70);
                imagedestroy($img);
                $images[] = url('/storage/images/' . $webpName);
            } else {
                // If still fails, save original but rename as .webp placeholder
                $storedPath = $file->storeAs('images', $webpName, 'public');
                $images[] = url('/storage/' . $storedPath);
            }
        } catch (\Throwable $final) {
            // Absolute last resort
            $storedPath = $file->store('images', 'public');
            $images[] = url('/storage/' . $storedPath);
            Log::error('🚨 Final fallback hit: ' . $final->getMessage());
        }
    }
        }

        // Store all images
        $validated['background_image'] = json_encode($images, JSON_UNESCAPED_SLASHES);

        // Handle video
        if ($request->hasFile('background_video')) {
            $videoPath = $request->file('background_video')->store('videos', 'public');
            $validated['background_video'] = url('/storage/' . $videoPath);
        } elseif (is_string($request->background_video)) {
            $validated['background_video'] = $request->background_video;
        }

        $validated['is_video'] = $request->boolean('is_video', false);

        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);
        $content->background_image = json_decode($content->background_image, true);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully (WebP + optimized)',
            'data'    => $content,
        ]);
    }
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
