<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionSection;

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
}
