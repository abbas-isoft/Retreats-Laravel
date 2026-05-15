<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FeaturedArrival;
use Illuminate\Support\Str;

class AdminFeaturedArrivalController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        $featured = FeaturedArrival::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($request->property_ids as $pid) {
            DB::table('featured_arrival_property')->insert([
                'featured_arrival_id' => $featured->id,
                'property_id' => $pid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->formatFeaturedResponse($featured);
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'property_ids' => 'required|array',
            'property_ids.*' => 'exists:property_contents,property_id',
        ]);

        $featured = FeaturedArrival::find($id);
        if (!$featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        $featured->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        DB::table('featured_arrival_property')->where('featured_arrival_id', $featured->id)->delete();

        foreach ($request->property_ids as $pid) {
            DB::table('featured_arrival_property')->insert([
                'featured_arrival_id' => $featured->id,
                'property_id' => $pid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->formatFeaturedResponse($featured, 'Featured section updated successfully.');
    }
    public function getAll()
    {
        $list = FeaturedArrival::orderBy('id', 'desc')->get();
        return response()->json(['status' => true, 'data' => $list]);
    }
    public function get($id)
    {
        $featured = FeaturedArrival::find($id);
        if (!$featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return $this->formatFeaturedResponse($featured, 'Featured properties fetched successfully.');
    }
    public function delete($id)
    {
        $featured = FeaturedArrival::find($id);
        if (!$featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        DB::table('featured_arrival_property')->where('featured_arrival_id', $id)->delete();
        $featured->delete();

        return response()->json(['status' => true, 'message' => 'Deleted successfully.']);
    }
    private function formatFeaturedResponse($featured, $message = 'Featured section created successfully.')
    {
        $propertyIds = DB::table('featured_arrival_property')
            ->where('featured_arrival_id', $featured->id)
            ->pluck('property_id');

        $properties = DB::table('property_contents')
            ->whereIn('property_id', $propertyIds)
            ->get()
            ->map(function ($property) {
                $images = DB::table('property_content_images')
                    ->where('property_content_id', $property->id)
                    ->select('id', 'property_content_id', 'photo_id', 'url', 'created_at', 'updated_at')
                    ->get()
                    ->map(function ($img) {
                        $img->url = $img->url && !str_starts_with($img->url, 'http') ? env('APP_URL') . '/' . $img->url : $img->url;
                        return $img;
                    });

                $property->images = $images;

                if (is_string($property->amenities)) {
                    $decoded = json_decode($property->amenities, true);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    $property->amenities = is_array($decoded)
                        ? $decoded
                        : array_filter(explode(',', $property->amenities));
                }

                if (!is_array($property->amenities)) {
                    $property->amenities = [];
                }

                if (property_exists($property, 'location_description')) {
                    $property->location_description = Str::limit($property->location_description, 50);
                }

                return $property;
            });

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'id'          => $featured->id,
                'title'       => $featured->title,
                'description' => $featured->description,
                'properties'  => $properties,
            ],
        ]);
    }
}
