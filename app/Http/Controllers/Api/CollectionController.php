<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Services\SeoSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    /**
     * GET API: Return all collections
     */
    public function index()
    {
        $collections = Collection::with('seoSetting')->get();

        return response()->json([
            'status' => true,
            'data' => CollectionResource::collection($collections),
        ]);
    }

    public function updateNavigation(Request $request, $id)
    {
        $validated = $request->validate([
            'show_in_navigation' => 'required|boolean',
        ]);

        $collection = Collection::find($id);
        if (! $collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        $collection->show_in_navigation = $validated['show_in_navigation'];
        $collection->save();
        $collection->load('seoSetting');

        return response()->json([
            'status' => true,
            'message' => 'Collection navigation visibility updated successfully',
            'data' => new CollectionResource($collection),
        ]);
    }

    /**
     * POST API: Update existing collection by title
     */
    public function store(UpdateCollectionRequest $request, SeoSettingsService $seoService)
    {
        $validated = $request->validated();
        $collection = Collection::where('title', $validated['title'])->first();

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $validated['image'] = 'storage/'.$imagePath;
        }

        if ($collection) {
            $seo = $validated['seo'] ?? null;
            unset($validated['seo']);

            DB::transaction(function () use ($collection, $validated, $seo, $seoService) {
                $collection->update($validated);
                if (is_array($seo)) {
                    $seoService->update($collection, $seo);
                }
            });

            Cache::forget('collections');
            Cache::forget('collection.'.$collection->getKey());
            $collection->load('seoSetting');

            return response()->json([
                'status' => true,
                'message' => 'Collection updated successfully',
                'data' => new CollectionResource($collection),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Collection with this title does not exist. Cannot create new.',
        ], 404);
    }

    /**
     * GET API: Filter properties by collection ID
     */
    public function filterByCollection($id)
    {
        $collection = Collection::with(['properties.images'])->find($id);

        if (! $collection) {
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
                            'url' => $image->url && ! str_starts_with($image->url, 'http') ? env('APP_URL').'/'.$image->url : $image->url,
                        ];
                    }),
                ];
            }),
        ]);
    }
}
