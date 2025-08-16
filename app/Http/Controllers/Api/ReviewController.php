<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /api/reviews?property_id=123
     * Fetch reviews (optionally filtered by property_id)
     */
    public function index(Request $request)
    {
        $section_title = "Every Stay has a Story";
        $section_subtitle = "Reviews from guests who have stayed in our luxury group accommodation.";

        $query = Review::query();

        // Filter by property_id if provided
        if ($request->has('property_id')) {
            $query->where('property_id', $request->get('property_id'));
        }

        $reviews = $query->get();

        return response()->json([
            'title' => $section_title,
            'subtitle' => $section_subtitle,
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    /**
     * POST /api/reviews
     * Store new review for a property
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id'    => 'required|integer|exists:property_contents,property_id',
            'review'         => 'required|string',
            'reviewer_name'  => 'required|string|max:255',
            'location'       => 'required|string|max:255',
        ]);

        $review = Review::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Review submitted successfully.',
            'data'    => new ReviewResource($review),
        ]);
    }
}
