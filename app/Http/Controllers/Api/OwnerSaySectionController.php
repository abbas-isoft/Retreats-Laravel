<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OwnerSaySectionResource;
use App\Models\OwnerSaySection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerSaySectionController extends Controller
{
    // GET (Always return single record)
    public function index()
    {
        $section = OwnerSaySection::firstOrCreate([]);
        return new OwnerSaySectionResource($section);
    }

    // POST (Create once, then update only)
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                 => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'owner_name'            => 'nullable|string|max:255',
            'secondary_title'       => 'nullable|string|max:255',
            'secondary_description' => 'nullable|string',
            'image'                 => 'nullable|string',
        ]);

        // get or create first record
        $section = OwnerSaySection::firstOrCreate([]);

        // handle image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);

            // delete old image if exists
            if ($section->image && file_exists(public_path($section->image))) {
                @unlink(public_path($section->image));
            }

            $data['image'] = 'images/'.$filename;
        }

        // update record
        $section->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Owner Say Section updated successfully',
            'data'    => new OwnerSaySectionResource($section->fresh()),
        ]);
    }
}
