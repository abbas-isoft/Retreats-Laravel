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
        $section_title = "Every Stay has a Story";
        $section_subtitle = "Reviews from guests who have stayed in our luxury group accommodation.";

        // Fetch reviews from Hostaway API
        $hostawayReviews = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('HOSTAWAY_API_TOKEN'),
        ])->get('https://api.hostaway.com/v1/reviews')->json();

        // If API returns empty or invalid, use temporary test review
        if (!is_array($hostawayReviews) || empty($hostawayReviews)) {
            $hostawayReviews = [
                [
                    'listingMapId' => 1,
                    'guestName' => 'Test Guest',
                    'publicReview' => 'This is a test review.',
                    'arrivalDate' => '2025-12-01',
                    'departureDate' => '2025-12-05'
                ]
            ];
        }

        // Filter by property_id if provided
        if ($request->has('property_id')) {
            $hostawayReviews = array_filter($hostawayReviews, function($review) use ($request) {
                return ($review['listingMapId'] ?? null) == $request->get('property_id');
            });
        }

        // Save/update each review in local database
        foreach ($hostawayReviews as $item) {
            Review::updateOrCreate(
                [
                    'property_id'   => $item['listingMapId'] ?? null,
                    'reviewer_name' => $item['guestName'] ?? 'Anonymous',
                ],
                [
                    'review'         => $item['publicReview'] ?? $item['privateFeedback'] ?? '',
                    'location' => $item['location'] ?? 'Unknown',
                    'arrival_date'   => $item['arrivalDate'] ?? null,
                    'departure_date' => $item['departureDate'] ?? null,
                ]
            );
        }

        // Fetch from local DB for API response
        $reviews = Review::when($request->has('property_id'), function($query) use ($request) {
                $query->where('property_id', $request->get('property_id'));
            })
            ->get();

        return response()->json([
            'title' => $section_title,
            'subtitle' => $section_subtitle,
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function store(Request $request)
    {
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
