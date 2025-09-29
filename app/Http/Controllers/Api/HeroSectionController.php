<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use App\Http\Resources\HeroSectionResource;
use Illuminate\Http\Request;

class HeroSectionController extends Controller
{
    public function show()
    {
        $hero = HeroSection::first();
        return new HeroSectionResource($hero);
    }

    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'subtitle'    => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            // 'button_link' => 'nullable|string|max:255',
            'background'  => 'nullable|string', // e.g. image filename or URL
        ]);

        // pehle record fetch karo
        $hero = HeroSection::first();

        if ($hero) {
            $hero->update($validated);
            $message = 'Hero section updated successfully.';
        } else {
            $hero = HeroSection::create($validated);
            $message = 'Hero section created successfully.';
        }

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => new HeroSectionResource($hero),
        ]);
    }
}
