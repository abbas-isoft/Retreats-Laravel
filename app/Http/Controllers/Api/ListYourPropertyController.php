<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ListYourProperty;

class ListYourPropertyController extends Controller
{
    // GET API - Fetch latest content
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
                'image' => asset('storage/' . $content->image), // or direct URL
                'button_text' => $content->button_text,
                'button_link' => $content->button_link,
            ]
        ]);
    }

    // POST API - Store new content
    public function store(Request $request)
    {
       $validated = $request->validate([
    'title' => 'required|string|max:255',
    'subtitle' => 'required|string|max:1000',
    'image' => 'nullable|string', // ← yeh karo
    'button_text' => 'required|string|max:255',
    'button_link' => 'required|string|max:255',
]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('list-property', 'public');
            $validated['image'] = $imagePath;
        }

        $content = ListYourProperty::create($validated);

        return response()->json([
            'status' => true,
            'message' => 'List Your Property content added successfully.',
            'data' => $content
        ]);
    }
}
