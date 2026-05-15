<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListYourProperty;

class ListYourPropertyController extends Controller
{
    public function index()
    {
        $content = ListYourProperty::latest()->first();

        if (!$content) {
            return response()->json([
                'status' => false,
                'message' => 'Content not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'List Your Property content fetched successfully.',
            'data' => [
                'title' => $content->title,
                'subtitle' => $content->subtitle,
                'image' => $content->image && !str_starts_with($content->image, 'http') ? env('ADMIN_URL') . '/' . ltrim($content->image, '/') : $content->image,
                'button_text' => $content->button_text,
                'button_link' => $content->button_link,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string|max:1000',
            'image' => 'nullable|string',
            'button_text' => 'required|string|max:255',
            'button_link' => 'required|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('list-property', 'public');
            $validated['image'] = 'storage/' . $imagePath;
        }

        $content = ListYourProperty::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'List Your Property content added successfully.',
            'data' => $content
        ]);
    }
}
