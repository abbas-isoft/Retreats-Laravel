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

        return response()->json([
            'status' => true,
            'data' => $section,
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
                $images[] = asset('storage/'.$path);
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


