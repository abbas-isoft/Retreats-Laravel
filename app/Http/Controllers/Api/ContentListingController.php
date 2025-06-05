<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Property;
use Illuminate\Http\Request;
use App\Models\ContentListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ContentListingResource;

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
