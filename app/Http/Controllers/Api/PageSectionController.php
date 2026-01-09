<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PageSection;
use App\Http\Resources\PageSectionResource;

class PageSectionController extends Controller
{
    
    public function show($section_name)
    {
        $section = PageSection::where('section_name', $section_name)->first();

        if (!$section) {
            return response()->json(['message' => 'Section not found'], 404);
        }

        return new PageSectionResource($section);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string',
            'data' => 'required|array',
        ]);

        $section = PageSection::where('section_name', $validated['section_name'])->first();

        if ($section) {
            $section->update([
                'data' => $validated['data'],
            ]);
        } else {
            $section = PageSection::create($validated);
        }

        return response()->json([                              
            'message' => 'Section saved successfully',
            'data' => new PageSectionResource($section),
        ], 200);
    }
}
