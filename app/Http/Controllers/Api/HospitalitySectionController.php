<?php

// app/Http/Controllers/Api/HospitalitySectionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HospitalitySection;

class HospitalitySectionController extends Controller
{
    public function index()
    {
        $section = HospitalitySection::first();

        if ($section) {
            $section->og_images = is_string($section->og_images)
                ? json_decode($section->og_images, true)
                : ($section->og_images ?? []);
            $section->schema_markup = is_string($section->schema_markup)
                ? json_decode($section->schema_markup, true)
                : ($section->schema_markup ?? []);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $section->id ?? null,
                'title' => $section->title ?? '',
                'subtitle' => $section->subtitle ?? '',
                'description' => $section->description ?? '',
                'seo' => [
                    'meta_title' => $section->meta_title ?? '',
                    'meta_description' => $section->meta_description ?? '',
                    'meta_keywords' => $section->meta_keywords ?? '',
                    'og' => [
                        'title' => $section->og_title ?? '',
                        'description' => $section->og_description ?? '',
                        'images' => $section->og_images ?? [],
                        'url' => $section->og_url ?? '',
                        'type' => $section->og_type ?? 'website',
                        'site_name' => $section->og_site_name ?? '',
                        'width' => $section->og_image_width ?? 1200,
                        'height' => $section->og_image_height ?? 630,
                    ],
                    'twitter' => [
                        'card' => $section->twitter_card ?? 'summary_large_image',
                        'title' => $section->twitter_title ?? '',
                        'description' => $section->twitter_description ?? '',
                        'image' => $section->twitter_image ?? '',
                        'site' => $section->twitter_site ?? '',
                    ],
                    'canonical_url' => $section->canonical_url ?? '',
                    'schema_markup' => $section->schema_markup ?? [],
                    'robots_txt' => $section->robots_txt ?? true,
                    'is_active' => $section->is_active ?? true,
                ]
            ]
        ]);
    }


    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',

            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',

            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_images' => 'nullable|array',
            'og_images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048',
            'og_url' => 'nullable|string',
            'og_type' => 'nullable|string',
            'og_site_name' => 'nullable|string',
            'og_image_width' => 'nullable|integer',
            'og_image_height' => 'nullable|integer',

            'twitter_card' => 'nullable|string',
            'twitter_title' => 'nullable|string',
            'twitter_description' => 'nullable|string',
            'twitter_image' => 'nullable|string',
            'twitter_site' => 'nullable|string',

            'canonical_url' => 'nullable|string',
            'schema_markup' => 'nullable|json',
            'robots_txt' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);
        $section = HospitalitySection::first() ?? new HospitalitySection();

        // Handle og images upload
        if ($request->hasFile('og_images')) {
            $images = [];
            foreach ($request->file('og_images') as $image) {
                $path = $image->store('uploads/og', 'public');
                $images[] = asset('storage/' . $path);
            }
            $validated['og_images'] = $images;
        }

        $section->fill($validated)->save();

        return response()->json([
            'status' => true,
            'message' => 'Hospitality section updated successfully.',
            'data' => $section,
        ]);
    }
}
