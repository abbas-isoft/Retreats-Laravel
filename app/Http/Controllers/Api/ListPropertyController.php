<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListPropertyResource;
use App\Models\ListProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\WhyPartnerBox;
use App\Http\Resources\WhyPartnerBoxResource;
use App\Models\OwnerSaySection;
use App\Http\Resources\OwnerSaySectionResource;

class ListPropertyController extends Controller
{
    // GET API
    public function show()
    {
        $page = ListProperty::firstOrCreate([]);
        return response()->json([
            'status' => true,
            'data'   => new ListPropertyResource($page),
        ]);
    }

    // POST API
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'subtitle'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:100',
            'hero_image'  => 'nullable|string',
        ]);

        $page = ListProperty::firstOrCreate([]);

        // Upload hero_image
        if ($request->hasFile('hero_image')) {
            $file = $request->file('hero_image');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);

            $validated['hero_image'] = 'images/' . $filename;

            // delete old image if exists
            if ($page->hero_image && file_exists(public_path($page->hero_image))) {
                @unlink(public_path($page->hero_image));
            }
        }

        $page->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'List Property page updated',
            'data'    => new ListPropertyResource($page->fresh()),
        ]);
    }

    public function GetListProperty()
    {
        $page    = ListProperty::firstOrCreate([]);
        $boxes   = WhyPartnerBox::all();
        $section = OwnerSaySection::firstOrCreate([]);

        // Attach relations so resource can access them
        $page->setRelation('whyPartnerBoxes', $boxes);
        $page->setRelation('ownerSay', $section);

        return response()->json([
            'status'  => true,
            'message' => 'List Property data fetched successfully',
            'data'    => new ListPropertyResource($page),
        ]);
    }


    public function PostListProperty(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | 1. Validate Inputs (nested structure)
    |--------------------------------------------------------------------------
    */
        $validated = $request->validate([
            // Hero Section
            'hero_section.title'       => 'nullable|string|max:255',
            'hero_section.subtitle'    => 'nullable|string|max:255',
            'hero_section.description' => 'nullable|string',
            'hero_section.button_text' => 'nullable|string|max:100',
            'hero_section.hero_image'  => 'nullable|string',

            // Why Partner Section
            'why_partner_section.section_title'           => 'required|string|max:255',
            'why_partner_section.boxes'                   => 'required|array|min:1',
            'why_partner_section.boxes.*.box_title'       => 'required|string|max:255',
            'why_partner_section.boxes.*.box_description' => 'nullable|string',

            // Testimonial Section
            'testimonial_section.title'                 => 'nullable|string|max:255',
            'testimonial_section.description'           => 'nullable|string',
            'testimonial_section.owner_name'            => 'nullable|string|max:255',
            'testimonial_section.secondary_title'       => 'nullable|string|max:255',
            'testimonial_section.secondary_description' => 'nullable|string',
            'testimonial_section.image'                 => 'nullable|string',
        ]);

        /*
    |--------------------------------------------------------------------------
    | 2. Handle Hero Section (ListProperty)
    |--------------------------------------------------------------------------
    */
        $heroData = $request->input('hero_section', []);
        $page = ListProperty::first(); // only one record
        if (!$page) {
            $page = new ListProperty();
        }
        $page->fill($heroData);
        $page->save();

        /*
    |--------------------------------------------------------------------------
    | 3. Handle WhyPartnerBox Section
    |--------------------------------------------------------------------------
    */
        $partnerData = $request->input('why_partner_section', []);
        $createdBoxes = [];

        if (!empty($partnerData['boxes'])) {
            // instead of truncate, update existing or create new
            WhyPartnerBox::query()->delete(); // clear all old first

            foreach ($partnerData['boxes'] as $box) {
                $createdBoxes[] = WhyPartnerBox::create([
                    'section_title'   => $partnerData['section_title'] ?? null,
                    'box_title'       => $box['box_title'],
                    'box_description' => $box['box_description'] ?? null,
                ]);
            }
        }

        /*
    |--------------------------------------------------------------------------
    | 4. Handle Testimonial Section (OwnerSaySection)
    |--------------------------------------------------------------------------
    */
        $testimonialData = $request->input('testimonial_section', []);
        $section = OwnerSaySection::first();
        if (!$section) {
            $section = new OwnerSaySection();
        }
        $section->fill($testimonialData);
        $section->save();

        /*
    |--------------------------------------------------------------------------
    | 5. Final Response
    |--------------------------------------------------------------------------
    */
        $boxes = WhyPartnerBox::all();

        return response()->json([
            'status'     => true,
            'message'    => 'List Property page and related sections updated successfully',
            'data'       => new ListPropertyResource($page->fresh()),
            'boxes'      => WhyPartnerBoxResource::collection($boxes),
            'owner_say'  => new OwnerSaySectionResource($section->fresh()),
        ]);
    }
}
