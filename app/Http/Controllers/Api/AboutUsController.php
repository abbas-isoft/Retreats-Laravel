<?php

namespace App\Http\Controllers\Api;

use App\Models\AboutUs;
use App\Http\Controllers\Controller;
use App\Http\Resources\AboutUsResource;
use Illuminate\Http\JsonResponse;

class AboutUsController extends Controller
{
    public function show(): JsonResponse
    {
        $about = AboutUs::first();

        if (!$about) {
            return response()->json([
                'status' => false,
                'message' => 'About Us content not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About Us content fetched successfully.',
            'data' => new AboutUsResource($about),
        ]);
    }
}
