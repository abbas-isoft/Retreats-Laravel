<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionSection;
use Illuminate\Http\Request;

class CollectionSectionController extends Controller
{
    public function show($key)
    {
        $section = CollectionSection::where('section_key', $key)->first();

        if (!$section) {
            return response()->json([
                'status' => false,
                'message' => 'Section not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'title' => $section->title,
                'description' => $section->description,
            ]
        ]);
    }
    public function storeOrUpdate(Request $request, $key)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);


        $section = CollectionSection::where('section_key', $key)->first();

        if ($section) {
            $section->update($validated);
            $message = 'Section updated successfully.';
        } else {
            $validated['section_key'] = $key;
            $section = CollectionSection::create($validated);
            $message = 'Section created successfully.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $section
        ]);
    }
}
