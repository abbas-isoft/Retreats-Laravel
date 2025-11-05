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

    // ✅ SEO fields
    'meta_title'       => 'nullable|string',
    'meta_description' => 'nullable|string',
    'meta_keywords'    => 'nullable|string',

    'og_title'         => 'nullable|string',
    'og_description'   => 'nullable|string',

    // ✅ Accept both file upload + URL
    'og_image'         => 'nullable',

    'canonical_url'    => 'nullable|string',
    
    // ✅ Accept raw JSON string (NOT array)
    'schema_markup'    => 'nullable|string',

    'robots_txt'       => 'nullable|boolean',
    'is_active'        => 'nullable|boolean',
]);

        $images = [];

        // ✅ Handle background images
        if ($request->hasFile('background_image')) {
            foreach ($request->file('background_image') as $file) {
                $allowedExtensions = ['jpg', 'jpeg', 'png'];
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, $allowedExtensions)) {
                    continue;
                }

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
                                imagepalettetotruecolor($src);
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

                        if ($width > $maxWidth || $height > $maxHeight) {
                            $ratio = min($maxWidth / $width, $maxHeight / $height);
                            $newWidth = (int)($width * $ratio);
                            $newHeight = (int)($height * $ratio);

                            $resized = imagecreatetruecolor($newWidth, $newHeight);
                            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                            imagedestroy($src);
                            $src = $resized;
                        }

                        if (!imagewebp($src, $savePath, 75)) {
                            throw new \Exception('GD WebP conversion failed.');
                        }

                        imagedestroy($src);
                    } else {
                        Image::load($imagePath)
                            ->fit(Fit::Max, 1920, 1080)
                            ->format('webp')
                            ->quality(80)
                            ->save($savePath);
                    }

                    $optimizerChain = OptimizerChainFactory::create();
                    $optimizerChain->optimize($savePath);

                    $images[] = url('/storage/images/' . $webpName);

                } catch (\Throwable $e) {
                    Log::warning('⚠️ WebP Conversion Failed: ' . $e->getMessage());
                    $storedPath = $file->storeAs('images', $webpName, 'public');
                    $images[] = url('/storage/' . $storedPath);
                }
            }

            $validated['background_image'] = json_encode($images, JSON_UNESCAPED_SLASHES);
        }

        // ✅ Video handling
        if ($request->hasFile('background_video')) {
            $videoPath = $request->file('background_video')->store('videos', 'public');
            $validated['background_video'] = url('/storage/' . $videoPath);
        } elseif (is_string($request->background_video)) {
            $validated['background_video'] = $request->background_video;
        }
        // ✅ OG IMAGE (SEO Thumbnail for social media)
if ($request->hasFile('og_image')) {
    $ogImagePath = $request->file('og_image')->store('seo', 'public');
    $validated['og_image'] = url('/storage/' . $ogImagePath);
} elseif (is_string($request->og_image)) {
    // If user sends URL instead of file
    $validated['og_image'] = $request->og_image;
}

        // ✅ Boolean & JSON handling
        $validated['is_video']   = $request->boolean('is_video', false);
        $validated['robots_txt'] = $request->boolean('robots_txt', true);
        $validated['is_active']  = $request->boolean('is_active', true);

        if ($request->schema_markup) {
            $validated['schema_markup'] = json_encode($request->schema_markup);
        }

        // ✅ Save homepage content (only one row)
        $content = HomepageContent::updateOrCreate(['id' => 1], $validated);

        $content->background_image = is_string($content->background_image)
            ? json_decode($content->background_image, true)
            : ($content->background_image ?? []);

        return response()->json([
            'status'  => true,
            'message' => 'Homepage content saved successfully (with SEO & Media)',
            'data'    => $content,
        ]);
    }

    // 🆕 For Spatie Image Upload
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:8192',
        ]);

        $image = $request->file('image');
        $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $path = storage_path('app/public/uploads/');

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $webpFile = $path . $filename . '.webp';

        Image::load($image->getRealPath())
            ->fit(Fit::Max, 2000, 2000)
            ->format('webp')
            ->quality(85)
            ->save($webpFile);

        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($webpFile);

        $publicUrl = asset('storage/uploads/' . basename($webpFile));

        return response()->json([
            'success' => true,
            'url' => $publicUrl,
        ]);
    }
}
