<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        // Fetch title and subtitle from database, fallback to static defaults
        $reviewSettings = Review::whereNotNull('title')->orWhereNotNull('subtitle')->first();
        $section_title = ($reviewSettings && $reviewSettings->title) ? $reviewSettings->title : "Every Stay has a Story";
        $section_subtitle = ($reviewSettings && $reviewSettings->subtitle) ? $reviewSettings->subtitle : "Reviews from guests who have stayed in our luxury group accommodation.";

        // Fetch reviews only from local database (CMS handled)
        $reviews = Review::whereNotNull('review')
            ->when($request->has('property_id'), function($query) use ($request) {
                $query->where('property_id', $request->get('property_id'));
            })
            ->latest()
            ->get();

        return response()->json([
            'title' => $section_title,
            'subtitle' => $section_subtitle,
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function store(Request $request)
    {
        // Check if updating Title/Subtitle settings
        if ($request->has('title') || $request->has('subtitle')) {
            $validated = $request->validate([
                'title'    => 'nullable|string|max:255',
                'subtitle' => 'nullable|string',
            ]);

            $settings = Review::whereNotNull('title')->orWhereNotNull('subtitle')->first();

            if ($settings) {
                $settings->update($validated);
            } else {
                $settings = Review::create($validated);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Review settings updated successfully.',
                'data'    => [
                    'title' => $settings->title,
                    'subtitle' => $settings->subtitle,
                ]
            ]);
        }

        // Otherwise, store a normal Review
        $validated = $request->validate([
            'property_id'    => 'required|integer',
            'review'         => 'required|string',
            'reviewer_name'  => 'required|string|max:255',
            'location'       => 'nullable|string|max:255',
        ]);

        $review = Review::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Review submitted successfully.',
            'data'    => new ReviewResource($review),
        ]);
    }
}
