<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Region;
use App\Models\PropertyContentImage;

class AdminCollectionController extends Controller
{
    public function createCollection(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png',
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/' . $path;
        }

        $collection = Collection::where('title', $request->title)->first();

        if ($collection) {
            $collection->update([
                'description' => $request->description,
                'image'       => $imageUrl ?? $collection->image,
            ]);

            DB::table('collection_property')->where('collection_id', $collection->id)->delete();
        } else {
            $collection = Collection::create([
                'title'       => $request->title,
                'description' => $request->description,
                'image'       => $imageUrl,
            ]);
        }
        if (strtolower($request->title) === 'late escapes') {
            if (!empty($request->property_ids)) {
                foreach ($request->property_ids as $propertyId) {
                    DB::table('collection_property')->insert([
                        'collection_id' => $collection->id,
                        'property_id'   => $propertyId,
                    ]);
                }
            }
        } else {
            foreach ($request->property_ids as $propertyId) {
                DB::table('collection_property')->insert([
                    'collection_id' => $collection->id,
                    'property_id'   => $propertyId,
                ]);
            }
        }
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
            ->whereIn('property_id', $request->property_ids)
            ->get();

        return response()->json([
            'status' => true,
            'message' => $collection->wasRecentlyCreated
                ? 'Collection created successfully'
                : 'Collection updated successfully',
            'data' => [
                'id'          => $collection->id,
                'name'        => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image'       => $collection->image && !str_starts_with($collection->image, 'http') ? env('ADMIN_URL') . '/' . $collection->image : $collection->image,
                'properties'  => $properties,
            ]
        ]);
    }
    public function deleteCollection($id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'status'  => false,
                'message' => 'Collection not found',
            ], 404);
        }

        DB::table('collection_property')->where('collection_id', $id)->delete();
        $collection->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Collection deleted successfully',
        ]);
    }
    public function getCollection($id)
    {
        $collection = Collection::find($id);

        if (!$collection) {
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
                    'id'          => $collection->id,
                    'name'        => $collection->title,
                    'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                    'image'       => $collection->image,
                    'properties'  => [],
                ]
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
                        $img->url = $img->url && !str_starts_with($img->url, 'http') ? env('APP_URL') . '/' . $img->url : $img->url;
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
                'id'          => $collection->id,
                'name'        => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image'       => $collection->image && !str_starts_with($collection->image, 'http') ? env('ADMIN_URL') . '/' . $collection->image : $collection->image,
                'properties'  => $properties,
            ]
        ]);
    }

    /**
     * Get all collections
     */
    public function getAllCollections()
    {
        $collections = Collection::all()->map(function ($collection) {
            return [
                'id'          => $collection->getKey(),
                'title'       => $collection->title,
                'description' => $collection->description,
                'show_in_navigation' => $collection->show_in_navigation,
                'image'       => $collection->image && !str_starts_with($collection->image, 'http') ? env('ADMIN_URL') . '/' . $collection->image : $collection->image,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $collections
        ]);
    }

    public function createRegion(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'property_ids'  => 'required|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        // Handle image upload
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/' . $path;
        }

        $region = Region::where('name', $request->name)->first();

        if ($region) {

            $region->update([
                'description' => $request->description,
                'image'       => $imageUrl ?? $region->image,
            ]);


            DB::table('region_property')->where('region_id', $region->id)->delete();
        } else {
            $region = Region::create([
                'name'        => $request->name,
                'description' => $request->description,
                'image'       => $imageUrl,
            ]);
        }

        foreach ($request->property_ids as $propertyId) {
            DB::table('region_property')->insert([
                'region_id'   => $region->id,
                'property_id' => $propertyId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

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
            ->whereIn('property_id', $request->property_ids)
            ->get();

        return response()->json([
            'status' => true,
            'message' => $region->wasRecentlyCreated
                ? 'Region created successfully'
                : 'Region updated successfully',
            'data' => [
                'id'          => $region->id,
                'name'        => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image'       => $region->image && !str_starts_with($region->image, 'http') ? env('ADMIN_URL') . '/' . $region->image : $region->image,
                'properties'  => $properties,
            ]
        ]);
    }

    public function updateRegion(Request $request, $id)
    {
        $region = Region::find($id);
        if (!$region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        $request->validate([
            'name'          => 'required|string|max:255|unique:regions,name,' . $id,
            'description'   => 'nullable|string',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'property_ids'  => 'nullable|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        $imageUrl = $region->image;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = 'storage/' . $path;
        }

        $region->update([
            'name'        => $request->name,
            'description' => $request->description,
            'image'       => $imageUrl,
        ]);

        if (!empty($request->property_ids)) {
            DB::table('region_property')->where('region_id', $region->id)->delete();

            foreach ($request->property_ids as $propertyId) {
                DB::table('region_property')->insert([
                    'region_id'   => $region->id,
                    'property_id' => $propertyId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
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
            ->whereIn('property_id', $request->property_ids ?? [])
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Region updated successfully',
            'data' => [
                'id'          => $region->id,
                'name'        => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image'       => $region->image && !str_starts_with($region->image, 'http') ? env('ADMIN_URL') . '/' . $region->image : $region->image,
                'properties'  => $properties,
            ]
        ]);
    }

    public function deleteRegion($id)
    {
        $region = Region::find($id);

        if (!$region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        DB::table('region_property')->where('region_id', $region->id)->delete();
        $region->delete();

        return response()->json(['status' => true, 'message' => 'Region deleted successfully']);
    }
    public function updateRegionNavigation(Request $request, $id)
    {
        $validated = $request->validate([
            'show_in_navigation' => 'required|boolean',
        ]);

        $region = Region::find($id);
        if (!$region) {
            return response()->json(['status' => false, 'message' => 'Region not found'], 404);
        }

        $region->show_in_navigation = $validated['show_in_navigation'];
        $region->save();

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
            ],
        ]);
    }
    //  Get all regions
    public function reorderRegions(Request $request)
    {
        $validated = $request->validate([
            'region_ids' => 'present|array|list',
            'region_ids.*' => 'required|integer|distinct',
        ]);

        $error = DB::transaction(function () use ($validated) {
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

        return $error ?? $this->getAllRegions();
    }

    public function getAllRegions()
    {
        $regions = Region::select('id', 'name', 'description', 'image', 'show_in_navigation', 'sort_order')
            ->orderBy('sort_order')->orderBy('id')->get()->map(function ($region) {
            $region->image = $region->image && !str_starts_with($region->image, 'http') ? env('ADMIN_URL') . '/' . $region->image : $region->image;
            return $region;
        });

        return response()->json(['status' => true, 'data' => $regions]);
    }

    public function getRegion($id)
    {
        $region = Region::find($id);

        if (!$region) {
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
                'id'          => $region->id,
                'name'        => $region->name,
                'description' => $region->description,
                'show_in_navigation' => $region->show_in_navigation,
                'sort_order' => $region->sort_order,
                'image'       => $region->image && !str_starts_with($region->image, 'http') ? env('ADMIN_URL') . '/' . $region->image : $region->image,
                'properties'  => $properties,
            ]
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

        if (!$regionRecord) {
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
                'total_properties' => 0
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
}
