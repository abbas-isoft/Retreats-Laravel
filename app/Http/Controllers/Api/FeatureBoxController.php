<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureBox;

class FeatureBoxController extends Controller
{
    public function index()
    {
       $boxes = FeatureBox::all()->map(function($box) {
        $box->icon = url('images/' . $box->icon); 
        return $box;
    });

        return response()->json([
            'status' => 'success',
            'data' => $boxes
        ]);
    }
}
