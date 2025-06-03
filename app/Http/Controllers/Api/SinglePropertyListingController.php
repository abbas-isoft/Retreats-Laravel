<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use App\Models\SingleConfiguration;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\SinglePropertyResource;

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
