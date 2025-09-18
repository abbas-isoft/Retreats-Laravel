<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyExtra;
use App\Http\Resources\PropertyExtraResource;

class PropertyExtraController extends Controller
{
    public function index($propertyId)
    {
        $extras = PropertyExtra::where('property_id', $propertyId)->latest()->get();
        return PropertyExtraResource::collection($extras);
    }
}
