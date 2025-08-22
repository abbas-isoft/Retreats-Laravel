<?php

namespace App\Http\Controllers;

use App\Models\AboutSection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AboutSectionController extends Controller
{
    // GET API to retrieve About Section data
    public function getAboutSection(): JsonResponse
    {
        $aboutSection = AboutSection::first();  // Assuming there is only one about section

        if (!$aboutSection) {
            return response()->json([
                'status' => false,
                'message' => 'About section not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'About section data fetched successfully',
            'data' => $aboutSection
        ], 200);
    }

    // POST API to update or create About Section data
    public function storeAboutSection(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $aboutSection = AboutSection::updateOrCreate(
            ['id' => 1], // Assuming a single record for the about section
            $validatedData
        );

        return response()->json([
            'status' => true,
            'message' => 'About section data updated successfully',
            'data' => $aboutSection
        ], 200);
    }

    public function GoFaqsSection(){


        
    }
}
