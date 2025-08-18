<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListPropertyResource;
use App\Models\ListProperty;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);

            $validated['hero_image'] = 'images/'.$filename;

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
}
