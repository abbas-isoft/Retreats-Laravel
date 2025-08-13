<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BookingGuide;
use Illuminate\Http\Request;
use App\Http\Resources\BookingGuideResource;

class BookingGuideController extends Controller
{

    public function index()
    {
        $guide = BookingGuide::first(); 
        
        if (!$guide) {
            return response()->json(['message' => 'No content found'], 404);
        }

        return new BookingGuideResource($guide);
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'subtitle' => 'required|string',
            'video_url' => 'nullable|string',
             'image' => 'nullable|string',
        ]);

       
        $guide = BookingGuide::first();

        if ($guide) {
            
            $guide->update($request->only(['title', 'subtitle', 'video_url']));
        } else {
           
            $guide = BookingGuide::create($request->only(['title', 'subtitle', 'video_url']));
        }

        return response()->json([
            'message' => 'Saved successfully',
            'data' => new BookingGuideResource($guide)
        ]);
    }
}
