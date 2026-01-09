<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OwnerSaySectionResource;
use App\Models\OwnerSaySection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerSaySectionController extends Controller
{
    public function index()
    {
        $section = OwnerSaySection::firstOrCreate([]);
        return new OwnerSaySectionResource($section);
    }
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
        $section = OwnerSaySection::firstOrCreate([]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images'), $filename);

            if ($section->image && file_exists(public_path($section->image))) {
                @unlink(public_path($section->image));
            }

            $data['image'] = 'images/' . $filename;
        }

        $section->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Owner Say Section updated successfully',
            'data'    => new OwnerSaySectionResource($section->fresh()),
        ]);
    }
}
