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
    /**
     * Create or update a collection with selected properties
     */
    public function createCollection(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png',
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        //  Handle image upload like ImageUploadController
        $imageUrl = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $imageUrl = asset('storage/' . $path);
        }

        //  Check if collection already exists
        $collection = Collection::where('title', $request->title)->first();

        if ($collection) {
            // Update existing
            $collection->update([
                'description' => $request->description,
                'image'       => $imageUrl ?? $collection->image,
            ]);

            // Delete old property relations
            DB::table('collection_property')->where('collection_id', $collection->id)->delete();
        } else {
            // Create new
            $collection = Collection::create([
                'title'       => $request->title,
                'description' => $request->description,
                'image'       => $imageUrl,
            ]);
        }

        //  If "Late Escapes" — admin manually selects properties
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
            //  Normal collections
            foreach ($request->property_ids as $propertyId) {
                DB::table('collection_property')->insert([
                    'collection_id' => $collection->id,
                    'property_id'   => $propertyId,
                ]);
            }
        }

        //  Fetch property details
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
                'image'       => $collection->image, //  already full URL
                'properties'  => $properties,
            ]
        ]);
    }

    /**
     * Delete a collection
     */
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

    /**
     * Get a collection with its related properties
     */
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
            ->whereIn('property_id', $propertyIds)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Collection fetched successfully',
            'data' => [
                'id'          => $collection->id,
                'name'        => $collection->title,
                'description' => $collection->description,
                'image'       => $collection->image, // already full URL
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
                'image'       => $collection->image,
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
        'name'          => 'required|string|max:255|unique:regions,name',
        'description'   => 'nullable|string',
        'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        'property_ids'  => 'required|array',
        'property_ids.*'=> 'exists:property_contents,property_id',
    ]);

    // Handle image upload
    $imageUrl = null;
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('images', 'public');
        $imageUrl = asset('storage/' . $path);
    }

    // Create region
    $region = Region::create([
        'name'        => $request->name,
        'description' => $request->description,
        'image'       => $imageUrl,
    ]);

    // Attach properties
    foreach ($request->property_ids as $propertyId) {
        DB::table('region_property')->insert([
            'region_id'   => $region->id,
            'property_id' => $propertyId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    // Fetch properties for response
    $properties = DB::table('property_contents')
        ->select('property_id', 'property_name', 'subcaption', 'description', 'city', 'country')
        ->whereIn('property_id', $request->property_ids)
        ->get();

    return response()->json([
        'status' => true,
        'message' => 'Region created successfully',
        'data' => [
            'id'          => $region->id,
            'name'        => $region->name,
            'description' => $region->description,
            'image'       => $region->image,
            'properties'  => $properties,
        ]
    ]);
}

    //  Update region name
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
        'property_ids.*'=> 'exists:property_contents,id',
    ]);

    $imageUrl = $region->image;
    if ($request->hasFile('image')) {
        $path = $request->file('image')->store('images', 'public');
        $imageUrl = asset('storage/' . $path);
    }

    $region->update([
        'name'        => $request->name,
        'description' => $request->description,
        'image'       => $imageUrl,
    ]);

    // Reassign properties if provided
    if ($request->has('property_ids')) {
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

    return response()->json([
        'status' => true,
        'message' => 'Region updated successfully',
        'data' => $region,
    ]);
}

    //  Delete region
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
    //  Get all regions
    public function getAllRegions()
{
    $regions = Region::select('id', 'name', 'description', 'image')->get();

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
            'image'       => $region->image,
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
        'region' => 'required|string',
    ]);

    $regionName = strtolower(trim($request->region));

    $regionRecord = DB::table('regions')
        ->whereRaw('LOWER(name) = ?', [$regionName])
        ->first();

    if (!$regionRecord) {
        return response()->json([
            'status' => false,
            'message' => 'Region not found in database',
        ], 404);
    }

    $regions = [
        'south west' => ['min_lat' => 50.0, 'max_lat' => 52.0, 'min_lng' => -5.7, 'max_lng' => -2.0],
        'south east' => ['min_lat' => 50.5, 'max_lat' => 52.5, 'min_lng' => -2.0, 'max_lng' => 1.5],
        'midlands' => ['min_lat' => 52.0, 'max_lat' => 54.0, 'min_lng' => -3.0, 'max_lng' => 0.5],
        'north of england' => ['min_lat' => 54.0, 'max_lat' => 56.5, 'min_lng' => -4.0, 'max_lng' => -0.5],
        'wales' => ['min_lat' => 51.3, 'max_lat' => 53.6, 'min_lng' => -5.5, 'max_lng' => -2.5],
        'scotland' => ['min_lat' => 55.0, 'max_lat' => 59.0, 'min_lng' => -6.0, 'max_lng' => -1.0],
        'coastal escapes' => ['min_lat' => 49.5, 'max_lat' => 51.0, 'min_lng' => -5.0, 'max_lng' => -3.0],
        'northern lights' => ['min_lat' => 57.0, 'max_lat' => 59.5, 'min_lng' => -6.5, 'max_lng' => -1.0],
        'central charm' => ['min_lat' => 52.5, 'max_lat' => 54.5, 'min_lng' => -2.5, 'max_lng' => 0.3],
    ];

    $bounds = $regions[$regionName] ?? null;

    if (!$bounds) {
        return response()->json([
            'status' => true,
            'region' => $regionRecord->name,
            'message' => 'No coordinates mapping found for this region name, but region exists in DB.',
            'properties' => [],
            'total_properties' => 0
        ]);
    }

    $properties = DB::table('property_contents')
        ->select(
            'id',
            'property_id',
            'property_name',
            'subcaption',
            'description',
            'accommodations_summary',
            'location_description',
            'address_line',
            'city',
            'country',
            'latitude',
            'longitude',
            'property_type',
            'registration_number',
            'epcRating',
            'bedroom_count',
            'bathroom_count',
            'area',
            'area_unit',
            'amenities'
        )
        ->whereBetween('latitude', [$bounds['min_lat'], $bounds['max_lat']])
        ->whereBetween('longitude', [$bounds['min_lng'], $bounds['max_lng']])
        ->get();

    // ✅ Attach property images with full object structure
    $propertiesWithImages = $properties->map(function ($property) {
        $property->images = \App\Models\PropertyContentImage::where('property_content_id', $property->id)->get();
        return $property;
    });

    return response()->json([
    'status' => true,
    'region' => $regionRecord->name,
    'total_properties' => $propertiesWithImages->count(),
    'properties' => $propertiesWithImages->map(function ($property) {
        // ✅ Fix double-encoded amenities
        if (isset($property->amenities)) {
            $decoded = $property->amenities;

            // If it's a string, try to decode once
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            // If still a string inside, decode again
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            // Ensure it’s an array
            $property->amenities = is_array($decoded) ? $decoded : [];
        }

        return $property;
    }),
    'message' => 'Properties fetched successfully',
]);
}
}
