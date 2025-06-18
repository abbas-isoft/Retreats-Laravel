<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SinglePropertyResource;
use App\Models\Property;
use App\Models\SingleConfiguration;
use Illuminate\Http\JsonResponse;

class SinglePropertyListingController extends Controller
{
    public function index($id): JsonResponse
    {
        $property = Property::find($id);

        return response()->json([
            'single-property-listing' => SinglePropertyResource::collection(SingleConfiguration::latest()->get()),
        ]);
    }
}
