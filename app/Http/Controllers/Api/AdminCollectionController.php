<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCollectionController extends Controller
{
    /**
     * Create a new collection with selected properties
     */
    public function createCollection(Request $request)
{
    $request->validate([
        'title'        => 'required|string|max:255',
        'description'  => 'nullable|string',
        'image'        => 'nullable|string',
        'property_ids' => 'required|array',
        'property_ids.*' => 'exists:property_contents,property_id',
    ]);

    // Check if collection with same title exists
    $collection = Collection::where('title', $request->title)->first();

    if ($collection) {
        // ✅ Update existing collection
        $collection->update([
            'description' => $request->description,
            'image'       => $request->image,
        ]);

        // Delete old properties
        DB::table('collection_property')->where('collection_id', $collection->id)->delete();
    } else {
        // ✅ Create new collection
        $collection = Collection::create([
            'title'       => $request->title,
            'description' => $request->description,
            'image'       => $request->image,
        ]);
    }

    // Attach new properties
    foreach ($request->property_ids as $propertyId) {
        DB::table('collection_property')->insert([
            'collection_id' => $collection->id,
            'property_id'   => $propertyId,
        ]);
    }

    // Fetch property details
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
            'image'       => $collection->image,
            'properties'  => $properties
        ]
    ]);
}

    public function deleteCollection($id)
{
    // Check if collection exists
    $collection = Collection::find($id);

    if (!$collection) {
        return response()->json([
            'status'  => false,
            'message' => 'Collection not found',
        ], 404);
    }

    // Delete pivot records first
    DB::table('collection_property')->where('collection_id', $id)->delete();

    // Delete collection
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
        // Find collection
        $collection = Collection::find($id);

        if (!$collection) {
            return response()->json([
                'status' => false,
                'message' => 'Collection not found'
            ], 404);
        }

        // Get property IDs from pivot table
        $propertyIds = DB::table('collection_property')
            ->where('collection_id', $collection->id)
            ->pluck('property_id');

        // Get property details
        $properties = DB::table('property_contents')
            ->select('property_id', 'property_name', 'subcaption', 'description', 'accommodations_summary', 'location_description', 'address_line', 'city', 'country')
            ->whereIn('property_id', $propertyIds)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Collection fetched successfully',
            'data' => [
                'id'          => $collection->id,
                'name'        => $collection->title,
                'description' => $collection->description,
                'image'       => $collection->image ? asset($collection->image) : null, // ✅ add base URL
                'properties'  => $properties
            ]
        ]);
    }

    public function getAllCollections()
{
    $collections = \App\Models\Collection::all()->map(function ($collection) {
        return [
            'id'          => $collection->getKey(), // 👈 Ye always primary key return karega
            'title'       => $collection->title,
            'description' => $collection->description,
            'image'       => $collection->image ? asset($collection->image) : null,
        ];
    });

    return response()->json([
        'status' => true,
        'data'   => $collections
    ]);
}

}
