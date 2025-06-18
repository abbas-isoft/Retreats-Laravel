<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentListingResource;
use App\Models\ContentListing;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class ContentListingController extends Controller
{
    public function index($id): JsonResponse
    {
        $property = Property::find($id);

        return response()->json([
            'content-listing' => ContentListingResource::collection(ContentListing::latest()->get()),
        ]);

    }
}
