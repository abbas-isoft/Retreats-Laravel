<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Http\Resources\CollectionResource;
use Illuminate\Support\Facades\Storage;

class CollectionController extends Controller
{
    /**
     * GET API: Return all collections
     */
    public function index()
    {
        $collections = Collection::all();

        return response()->json([
            'status' => true,
            'data' => CollectionResource::collection($collections),
        ]);
    }

    /**
     * POST API: Update existing collection by title
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

      
        $collection = Collection::where('title', $validated['title'])->first();

        
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $validated['image'] = '/storage/' . $imagePath;
        }

        if ($collection) {
            
            $collection->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Collection updated successfully',
                'data' => new CollectionResource($collection),
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Collection with this title does not exist. Cannot create new.',
            ], 404);
        }
    }

    /**
     * GET API: Filter properties by collection ID
     */
    public function filterByCollection($id)
    {
        $collection = Collection::with(['properties.images'])->find($id);

        if (!$collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'collection_title' => $collection->title,
            'properties' => $collection->properties->map(function ($property) {
                return [
                    'id' => $property->id,
                    'listingUrl' => $property->listingUrl,
                    'images' => $property->images->map(function ($image) {
                        return [
                            'photo_id' => $image->photo_id,
                            'url' => asset($image->image_url),
                        ];
                    }),
                ];
            }),
        ]);
    }
}
