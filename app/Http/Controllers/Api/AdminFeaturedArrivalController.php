<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeaturedArrival;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminFeaturedArrivalController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'property_ids' => 'required|array|list',
            'property_ids.*' => 'required|integer|distinct|exists:property_contents,property_id',
        ]);

        $featured = FeaturedArrival::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($request->property_ids as $position => $pid) {
            DB::table('featured_arrival_property')->insert([
                'featured_arrival_id' => $featured->id,
                'property_id' => $pid,
                'sort_order' => $position + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->clearFeaturedArrivalCache($featured->id);

        return $this->formatFeaturedResponse($featured);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'property_ids' => 'required|array|list',
            'property_ids.*' => 'required|integer|distinct|exists:property_contents,property_id',
        ]);

        $featured = FeaturedArrival::find($id);
        if (! $featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        $featured->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        DB::transaction(function () use ($featured, $request) {
            FeaturedArrival::whereKey($featured->id)->lockForUpdate()->firstOrFail();
            DB::table('featured_arrival_property')->where('featured_arrival_id', $featured->id)
                ->whereNotIn('property_id', $request->property_ids)->delete();
            foreach ($request->property_ids as $position => $pid) {
                $query = DB::table('featured_arrival_property')->where('featured_arrival_id', $featured->id)->where('property_id', $pid);
                if (! $query->exists()) {
                    $next = (int) DB::table('featured_arrival_property')->where('featured_arrival_id', $featured->id)->max('sort_order') + 1;
                    DB::table('featured_arrival_property')->insert([
                        'featured_arrival_id' => $featured->id, 'property_id' => $pid,
                        'sort_order' => $next, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                // This endpoint receives the complete ordered selection.
                $query->update(['sort_order' => $position + 1, 'updated_at' => now()]);
            }
        });

        $this->clearFeaturedArrivalCache($featured->id);

        return $this->formatFeaturedResponse($featured, 'Featured section updated successfully.');
    }

    public function getAll()
    {
        $list = FeaturedArrival::orderBy('id', 'desc')->get();

        return response()->json(['status' => true, 'data' => $list]);
    }

    public function reorder(Request $request, $id)
    {
        $result = DB::transaction(function () use ($id, $request) {
            $validated = $request->validate([
                'property_ids' => 'present|array|list',
                'property_ids.*' => 'required|integer|distinct|exists:property_contents,property_id',
            ]);

            $featured = FeaturedArrival::whereKey($id)->lockForUpdate()->first();
            if (! $featured) {
                return response()->json(['status' => false, 'message' => 'Not found'], 404);
            }
            $rows = DB::table('featured_arrival_property')
                ->where('featured_arrival_id', $id)->lockForUpdate()->get();
            $requested = collect($validated['property_ids'])->map(fn ($value) => (string) (int) $value);
            $existing = $rows->pluck('property_id')->map(fn ($value) => (string) (int) $value);
            if ($requested->unique()->count() !== $requested->count()
                || $requested->count() !== $existing->count()
                || $requested->diff($existing)->isNotEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Provide all currently selected property IDs exactly once',
                    'expected_property_ids' => $existing->values(),
                ], 422);
            }

            foreach ($requested->values() as $position => $propertyId) {
                $pivotIds = $rows->filter(fn ($row) => (string) (int) $row->property_id === $propertyId)->pluck('id');
                DB::table('featured_arrival_property')
                    ->where('featured_arrival_id', $id)->whereIn('id', $pivotIds)
                    ->update(['sort_order' => $position + 1, 'updated_at' => now()]);
            }

            return $featured;
        });

        if (! $result instanceof FeaturedArrival) {
            return $result;
        }

        $this->clearFeaturedArrivalCache($result->id);

        return $this->formatFeaturedResponse($result, 'Featured property order updated successfully.');
    }

    public function get($id)
    {
        $featured = FeaturedArrival::find($id);
        if (! $featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        return $this->formatFeaturedResponse($featured, 'Featured properties fetched successfully.');
    }

    public function delete($id)
    {
        $featured = FeaturedArrival::find($id);
        if (! $featured) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }

        DB::table('featured_arrival_property')->where('featured_arrival_id', $id)->delete();
        $featured->delete();
        $this->clearFeaturedArrivalCache($id);

        return response()->json(['status' => true, 'message' => 'Deleted successfully.']);
    }

    private function formatFeaturedResponse($featured, $message = 'Featured section created successfully.')
    {
        $selected = DB::table('featured_arrival_property')
            ->where('featured_arrival_id', $featured->id)
            ->orderBy('sort_order')->orderBy('property_id')->get();
        $propertyIds = $selected->pluck('property_id');
        $positions = $propertyIds->flip();
        $sortOrders = $selected->pluck('sort_order', 'property_id');

        $imagesByProperty = DB::table('property_content_images')
            ->whereIn('property_content_id', DB::table('property_contents')->whereIn('property_id', $propertyIds)->select('id'))
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('property_content_id');

        $properties = DB::table('property_contents')
            ->whereIn('property_id', $propertyIds)
            ->get()
            ->sortBy(fn ($property) => $positions->get($property->property_id))
            ->values()
            ->map(function ($property) use ($sortOrders, $imagesByProperty) {
                $property->sort_order = (int) $sortOrders->get($property->property_id);
                $images = $imagesByProperty->get($property->id, collect())
                    ->map(function ($img) {
                        $img->url = $img->url && ! str_starts_with($img->url, 'http') ? env('APP_URL').'/'.$img->url : $img->url;

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

                if (! is_array($property->amenities)) {
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
                'id' => $featured->id,
                'title' => $featured->title,
                'description' => $featured->description,
                'properties' => $properties,
            ],
        ]);
    }

    private function clearFeaturedArrivalCache(int|string $id): void
    {
        Cache::forget('featured-arrivals');
        Cache::forget('featured-arrivals.'.$id);
    }
}
