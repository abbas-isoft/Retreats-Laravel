<?php

namespace App\Http\Controllers\Api;

use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\PropertyContent;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use App\Http\Resources\PropertyContentResource;

class PropertyContentController extends Controller
{
    public function index($id): JsonResponse
    {
        $property = Property::findOrFail($id);

         return response()->json([
            'property-content' => PropertyContentResource::collection(PropertyContent::latest()->get()),
        ]);
    }
}
