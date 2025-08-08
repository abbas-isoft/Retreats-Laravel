<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;

class ReviewController extends Controller
{
    public function index()
    {
        $section_title = "Every Stay has a Story";
        $section_subtitle = "Reviews from guests who have stayed in our luxury group accommodation.";
        $reviews = Review::all();

        return response()->json([
            'title' => $section_title,
            'subtitle' => $section_subtitle,
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }
}
