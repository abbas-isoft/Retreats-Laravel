<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCollectionRequest;
use App\Http\Requests\SaveRegionRequest;
use App\Models\Collection;
use App\Models\Region;
use App\Services\SeoSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminCollectionController extends Controller
{
    public function createCollection(SaveCollectionRequest $request, SeoSettingsService $seoService)
    {
        $validated = $request->validated();

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/'.$path;
        }

        $collection = DB::transaction(function () use ($validated, $imageUrl, $seoService) {
            $collection = Collection::where('title', $validated['title'])->lockForUpdate()->first();

            if ($collection) {
                $attributes = ['description' => $validated['description'] ?? null];
                if ($imageUrl !== null) {
                    $attributes['image'] = $imageUrl;
                }
                $collection->update($attributes);
                DB::table('collection_property')->where('collection_id', $collection->id)->delete();
            } else {
                $collection = Collection::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'image' => $imageUrl,
                ]);
            }

            if (strtolower($validated['title']) === 'late escapes') {
                if (! empty($validated['property_ids'])) {
                    foreach ($validated['property_ids'] as $propertyId) {
                        DB::table('collection_property')->insert([
                            'collection_id' => $collection->id,
                            'property_id' => $propertyId,
                        ]);
                    }
                }
            } else {
                foreach ($validated['property_ids'] as $propertyId) {
                    DB::table('collection_property')->insert([
                        'collection_id' => $collection->id,
                        'property_id' => $propertyId,
                    ]);
                }
            }

            if (isset($validated['seo']) && is_array($validated['seo'])) {
                $seoService->update($collection, $validated['seo']);
            }

            return $collection;
        });

        Cache::forget('collections');
        Cache::forget('collection.'.$collection->getKey());
        $collection->load('seoSetting');

        $properties = DB::table('property_contents')
            ->select(
                'property_id',
                'property_name',
                'subcaption',
                'description',
                'accommodations_summary',
                'location_description',
                'address_line',
                'city',
                'country'
            )
            ->whereIn('property_id', $validated['property_ids'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => $collection->wasRecentlyCreated
                ? 'Collection created successfully'
                : 'Collection updated successfully',
            'data' => [
                'id' => $collection->id,
                'name' => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image' => $collection->image && ! str_starts_with($collection->image, 'http') ? env('ADMIN_URL').'/'.$collection->image : $collection->image,
                'properties' => $properties,
                'seo' => $collection->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    public function deleteCollection($id)
    {
        $collection = Collection::find($id);

        if (! $collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        DB::table('collection_property')->where('collection_id', $id)->delete();
        $collection->delete();
        Cache::forget('collections');
        Cache::forget('collection.'.$id);

        return response()->json([
            'status' => true,
            'message' => 'Collection deleted successfully',
        ]);
    }

    public function getCollection($id)
    {
        $collection = Collection::with('seoSetting')->find($id);

        if (! $collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found',
            ], 404);
        }

        $propertyIds = DB::table('collection_property')
            ->where('collection_id', $collection->id)
            ->pluck('property_id');

        if ($propertyIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No properties linked to this collection yet.',
                'data' => [
                    'id' => $collection->id,
                    'name' => $collection->title,
                    'description' => $collection->description,
                    'show_in_navigation' => $collection->show_in_navigation,
                    'image' => $collection->image,
                    'properties' => [],
                    'seo' => $collection->seoSetting?->toSeoArray(),
                ],
            ]);
        }

        $properties = DB::table('property_contents')
            ->leftJoin('content_listings', 'property_contents.property_id', '=', 'content_listings.property_id')
            ->select(
                'property_contents.id',
                'property_contents.property_id',
                'property_contents.customer_property_id',
                'property_contents.active',
                'property_contents.property_name',
                'property_contents.subcaption',
                'property_contents.description',
                'property_contents.accommodations_summary',
                'property_contents.location_description',
                'property_contents.changeover',
                'property_contents.second',
                'property_contents.address_line',
                'property_contents.city',
                'property_contents.country',
                'property_contents.post_code',
                'property_contents.latitude',
                'property_contents.longitude',
                'property_contents.property_type',
                'property_contents.registration_number',
                'property_contents.epcRating',
                'property_contents.bedroom_count',
                'property_contents.bathroom_count',
                'property_contents.area',
                'property_contents.area_unit',
                'content_listings.maximum_guests',
                'property_contents.admin_amenities',
                'property_contents.created_at',
                'property_contents.updated_at'
            )
            ->whereIn('property_contents.property_id', $propertyIds)
            ->get()
            ->map(function ($property) {
                $property->images = DB::table('property_content_images')
                    ->where('property_content_id', $property->id)
                    ->select('id', 'property_content_id', 'photo_id', 'url', 'caption', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($img) {
                        $img->url = $img->url && ! str_starts_with($img->url, 'http') ? env('APP_URL').'/'.$img->url : $img->url;

                        return $img;
                    });
                if (isset($property->admin_amenities)) {
                    $decoded = $property->admin_amenities;
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    $property->admin_amenities = is_array($decoded) ? $decoded : [];
                }

                return $property;
            });

        return response()->json([
            'status' => true,
            'message' => 'Filtered properties fetched successfully.',
            'data' => [
                'id' => $collection->id,
                'name' => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image' => $collection->image && ! str_starts_with($collection->image, 'http') ? env('ADMIN_URL').'/'.$collection->image : $collection->image,
                'properties' => $properties,
                'seo' => $collection->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    /**
     * Get all collections
     */
    public function getAllCollections()
    {
        $collections = Collection::with('seoSetting')->get()->map(function ($collection) {
            return [
                'id' => $collection->getKey(),
                'title' => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image' => $collection->image && ! str_starts_with($collection->image, 'http') ? env('ADMIN_URL').'/'.$collection->image : $collection->image,
                'seo' => $collection->seoSetting?->toSeoArray(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $collections,
        ]);
    }

    public function createRegion(SaveRegionRequest $request, SeoSettingsService $seoService)
    {
        $validated = $request->validated();

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/'.$path;
        }

        $region = DB::transaction(function () use ($validated, $imageUrl, $seoService) {
            $region = Region::where('name', $validated['name'])->lockForUpdate()->first();

            if ($region) {
                $attributes = ['description' => $validated['description'] ?? null];
                if ($imageUrl !== null) {
                    $attributes['image'] = $imageUrl;
                }
                $region->update($attributes);
                DB::table('region_property')->where('region_id', $region->id)->delete();
            } else {
                $region = Region::create([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'image' => $imageUrl,
                ]);
            }

            foreach ($validated['property_ids'] as $propertyId) {
                DB::table('region_property')->insert([
                    'region_id' => $region->id,
                    'property_id' => $propertyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (isset($validated['seo']) && is_array($validated['seo'])) {
                $seoService->update($region, $validated['seo']);
            }

            return $region;
        });

        $this->clearRegionsCache($region->id);
        $region->load('seoSetting');

        $properties = DB::table('property_contents')
            ->select(
                'property_id',
                'property_name',
                'subcaption',
                'description',
                'accommodations_summary',
                'location_description',
                'address_line',
                'city',
                'country'
            )
            ->whereIn('property_id', $validated['property_ids'])
            ->get();

        return response()->json([
            'status' => true,
            'message' => $region->wasRecentlyCreated
                ? 'Region created successfully'
                : 'Region updated successfully',
            'data' => [
                'id' => $region->id,
                'name' => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image' => $region->image && ! str_starts_with($region->image, 'http') ? env('ADMIN_URL').'/'.$region->image : $region->image,
                'properties' => $properties,
                'seo' => $region->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    public function updateRegion(SaveRegionRequest $request, SeoSettingsService $seoService, $id)
    {
        $region = Region::find($id);
        if (! $region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        $validated = $request->validated();

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/'.$path;
        }

        DB::transaction(function () use ($region, $validated, $imageUrl, $seoService) {
            $attributes = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ];
            if ($imageUrl !== null) {
                $attributes['image'] = $imageUrl;
            }
            $region->update($attributes);

            if (! empty($validated['property_ids'])) {
                DB::table('region_property')->where('region_id', $region->id)->delete();

                foreach ($validated['property_ids'] as $propertyId) {
                    DB::table('region_property')->insert([
                        'region_id' => $region->id,
                        'property_id' => $propertyId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (isset($validated['seo']) && is_array($validated['seo'])) {
                $seoService->update($region, $validated['seo']);
            }
        });

        $this->clearRegionsCache($region->id);
        $region->load('seoSetting');

        $properties = DB::table('property_contents')
            ->select(
                'property_id',
                'property_name',
                'subcaption',
                'description',
                'accommodations_summary',
                'location_description',
                'address_line',
                'city',
                'country'
            )
            ->whereIn('property_id', $validated['property_ids'] ?? [])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Region updated successfully',
            'data' => [
                'id' => $region->id,
                'name' => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image' => $region->image && ! str_starts_with($region->image, 'http') ? env('ADMIN_URL').'/'.$region->image : $region->image,
                'properties' => $properties,
                'seo' => $region->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    public function deleteRegion($id)
    {
        $region = Region::find($id);

        if (! $region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        DB::table('region_property')->where('region_id', $region->id)->delete();
        $region->delete();
        $this->clearRegionsCache($region->id);

        return response()->json(['status' => true, 'message' => 'Region deleted successfully']);
    }

    public function updateRegionNavigation(Request $request, $id)
    {
        $validated = $request->validate([
            'show_in_navigation' => 'required|boolean',
        ]);

        $region = Region::find($id);
        if (! $region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        $region->show_in_navigation = $validated['show_in_navigation'];
        $region->save();
        $this->clearRegionsCache($region->id);
        $region->load('seoSetting');

        return response()->json([
            'status' => true,
            'message' => 'Region navigation visibility updated successfully',
            'data' => [
                'id' => $region->id,
                'name' => $region->name,
                'description' => $region->description,
                'image' => $region->image,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'seo' => $region->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    //  Get all regions
    public function reorderRegions(Request $request)
    {
        $error = DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'region_ids' => 'present|array|list',
                'region_ids.*' => 'required|integer|distinct|exists:regions,id',
            ]);

            $existing = DB::table('regions')->orderBy('id')->lockForUpdate()->get(['id']);
            $expected = $existing->pluck('id')->map(fn ($id) => (string) (int) $id);
            $requested = collect($validated['region_ids'])->map(fn ($id) => (string) (int) $id);

            if ($requested->unique()->count() !== $requested->count()
                || $requested->count() !== $expected->count()
                || $requested->diff($expected)->isNotEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Provide all region IDs exactly once, including hidden regions',
                    'expected_region_ids' => $expected->values(),
                ], 422);
            }

            foreach ($requested->values() as $position => $id) {
                DB::table('regions')->where('id', $id)
                    ->update(['sort_order' => $position + 1, 'updated_at' => now()]);
            }

            return null;
        });

        if ($error) {
            return $error;
        }

        $this->clearRegionsCache();

        return $this->getAllRegions();
    }

    public function getAllRegions()
    {
        $regions = Region::with('seoSetting')
            ->select('id', 'name', 'description', 'image', 'show_in_navigation', 'sort_order')
            ->orderBy('sort_order')->orderBy('id')->get()->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'description' => $region->description,
                    'image' => $region->image,
                    'show_in_navigation' => $region->show_in_navigation,
                    'sort_order' => $region->sort_order,
                    'seo' => $region->seoSetting?->toSeoArray(),
                ];
            });

        return response()->json(['status' => true, 'data' => $regions]);
    }

    public function getRegion($id)
    {
        $region = Region::with('seoSetting')->find($id);

        if (! $region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        $propertyIds = DB::table('region_property')->where('region_id', $region->id)->pluck('property_id');

        $properties = DB::table('property_contents')
            ->select('property_id', 'property_name', 'subcaption', 'description', 'city', 'country')
            ->whereIn('property_id', $propertyIds)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Region fetched successfully',
            'data' => [
                'id' => $region->id,
                'name' => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image' => $region->image && ! str_starts_with($region->image, 'http') ? env('ADMIN_URL').'/'.$region->image : $region->image,
                'properties' => $properties,
                'seo' => $region->seoSetting?->toSeoArray(),
            ],
        ]);
    }

    //  Get properties by region name (same logic)
    //     public function getRegionDestinations(Request $request)
    // {
    //     $request->validate([
    //         'region' => 'required|string',
    //     ]);

    //     // Convert region name to lowercase for comparison
    //     $regionName = strtolower(trim($request->region));

    //     //  Fetch region dynamically from DB
    //     $regionRecord = DB::table('regions')
    //         ->whereRaw('LOWER(name) = ?', [$regionName])
    //         ->first();

    //     if (!$regionRecord) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Region not found in database',
    //         ], 404);
    //     }

    //     //  Define region boundaries (you can later move these to DB if needed)
    //     $regions = [
    //         'south west' => ['min_lat' => 50.0, 'max_lat' => 52.0, 'min_lng' => -5.7, 'max_lng' => -2.0],
    //         'south east' => ['min_lat' => 50.5, 'max_lat' => 52.5, 'min_lng' => -2.0, 'max_lng' => 1.5],
    //         'midlands' => ['min_lat' => 52.0, 'max_lat' => 54.0, 'min_lng' => -3.0, 'max_lng' => 0.5],
    //         'north of england' => ['min_lat' => 54.0, 'max_lat' => 56.5, 'min_lng' => -4.0, 'max_lng' => -0.5],
    //         'wales' => ['min_lat' => 51.3, 'max_lat' => 53.6, 'min_lng' => -5.5, 'max_lng' => -2.5],
    //         'scotland' => ['min_lat' => 55.0, 'max_lat' => 59.0, 'min_lng' => -6.0, 'max_lng' => -1.0],
    //         'coastal escapes' => ['min_lat' => 49.5, 'max_lat' => 51.0, 'min_lng' => -5.0, 'max_lng' => -3.0],
    //         'northern lights' => ['min_lat' => 57.0, 'max_lat' => 59.5, 'min_lng' => -6.5, 'max_lng' => -1.0],
    //         'central charm' => ['min_lat' => 52.5, 'max_lat' => 54.5, 'min_lng' => -2.5, 'max_lng' => 0.3],
    //     ];

    //     //  If region name exists in mapping, use that — otherwise fallback to default bounds
    //     $bounds = $regions[$regionName] ?? null;

    //     if (!$bounds) {
    //         return response()->json([
    //             'status' => true,
    //             'region' => $regionRecord->name,
    //             'message' => 'No coordinates mapping found for this region name, but region exists in DB.',
    //             'properties' => [],
    //             'total_properties' => 0
    //         ]);
    //     }

    //     //  Fetch properties from within that region’s coordinate box
    //     $properties = DB::table('property_contents')
    //         ->select(
    //             'property_id',
    //             'property_name',
    //             'subcaption',
    //             'description',
    //             'accommodations_summary',
    //             'location_description',
    //             'address_line',
    //             'city',
    //             'country',
    //             'latitude',
    //             'longitude'
    //         )
    //         ->whereBetween('latitude', [$bounds['min_lat'], $bounds['max_lat']])
    //         ->whereBetween('longitude', [$bounds['min_lng'], $bounds['max_lng']])
    //         ->get();

    //     return response()->json([
    //         'status' => true,
    //         'region' => $regionRecord->name, //  Always return updated name from DB
    //         'properties' => $properties,
    //         'total_properties' => $properties->count(),
    //     ]);
    // }

    public function getRegionDestinations(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions,id',
        ]);

        // Fetch region record by ID
        $regionRecord = DB::table('regions')
            ->where('id', $request->region_id)
            ->first();

        if (! $regionRecord) {
            return response()->json([
                'status' => false,
                'message' => 'Region not found in database',
            ], 404);
        }

        $propertyIds = DB::table('region_property')
            ->where('region_id', $regionRecord->id)
            ->pluck('property_id');

        if ($propertyIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'region' => $regionRecord->name,
                'message' => 'No properties linked to this region yet.',
                'properties' => [],
                'total_properties' => 0,
            ]);
        }
        $properties = DB::table('property_contents')
            ->leftJoin('content_listings', 'property_contents.property_id', '=', 'content_listings.property_id')
            ->select(
                'property_contents.id',
                'property_contents.property_id',
                'property_contents.property_name',
                'property_contents.subcaption',
                'property_contents.description',
                'property_contents.accommodations_summary',
                'property_contents.location_description',
                'property_contents.address_line',
                'property_contents.city',
                'property_contents.country',
                'property_contents.latitude',
                'property_contents.longitude',
                'property_contents.property_type',
                'property_contents.registration_number',
                'property_contents.epcRating',
                'property_contents.bedroom_count',
                'property_contents.bathroom_count',
                'property_contents.area',
                'property_contents.area_unit',
                'content_listings.maximum_guests',
                'property_contents.admin_amenities'
            )
            ->whereIn('property_contents.property_id', $propertyIds)
            ->get();

        $propertiesWithImages = $properties->map(function ($property) {
            $property->images = \App\Models\PropertyContentImage::where('property_content_id', $property->id)->get();

            if (isset($property->admin_amenities)) {
                $decoded = $property->admin_amenities;
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }
                $property->admin_amenities = is_array($decoded) ? $decoded : [];
            }

            return $property;
        });

        return response()->json([
            'status' => true,
            'region' => $regionRecord->name,
            'total_properties' => $propertiesWithImages->count(),
            'properties' => $propertiesWithImages,
            'message' => 'Properties fetched successfully',
        ]);
    }

    private function clearRegionsCache(int|string|null $id = null): void
    {
        Cache::forget('regions');
        if ($id !== null) {
            Cache::forget('region.'.$id);
        }
    }
}
