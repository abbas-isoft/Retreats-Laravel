<?php

// app/Http/Controllers/Api/PropertyGridController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;

class PropertyGridController extends Controller
{
    /**
     * Get all properties with their images for the property grid.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Fetch all properties with their images
        $properties = PropertyContent::with('images')->get();

        // Return the properties in the response
        return response()->json([
            'status' => true,
            'message' => 'All properties fetched successfully.',
            'properties' => $properties,
        ]);
    }
}
