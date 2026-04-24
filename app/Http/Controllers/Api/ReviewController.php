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
        $section_title = $reviewSettings->title ?? "Every Stay has a Story";
        $section_subtitle = $reviewSettings->subtitle ?? "Reviews from guests who have stayed in our luxury group accommodation.";

        // Fetch reviews from Hostaway API
        $hostawayReviews = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('HOSTAWAY_API_TOKEN'),
        ])->get('https://api.hostaway.com/v1/reviews')->json();

    
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
        if ($request->has('property_id')) {
            $hostawayReviews = array_filter($hostawayReviews, function($review) use ($request) {
                return ($review['listingMapId'] ?? null) == $request->get('property_id');
            });
        }

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

        $reviews = Review::whereNotNull('review')
            ->when($request->has('property_id'), function($query) use ($request) {
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
