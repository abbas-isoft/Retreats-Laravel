<?php

namespace App\Http\Controllers;

use App\Models\FAQSection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FAQSectionController extends Controller
{
    // GET API to retrieve FAQ Section data
    public function getFAQSection(): JsonResponse
    {
        $faqSection = FAQSection::all();

        if ($faqSection->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No FAQ data found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'FAQ section data fetched successfully',
            'data' => $faqSection
        ], 200);
    }

    // POST API to add or update FAQ Section data
    public function storeFAQSection(Request $request): JsonResponse
    {
        // Validate incoming data
        $validatedData = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        // Check if an ID is provided (for updating an existing FAQ)
        if ($request->has('id') && $request->id != null) {
            // Update the existing FAQ if an ID is provided
            $faqSection = FAQSection::find($request->id);

            if (!$faqSection) {
                return response()->json([
                    'status' => false,
                    'message' => 'FAQ not found'
                ], 404);
            }

            $faqSection->update($validatedData);
            $message = 'FAQ section data updated successfully';
        } else {
            // If no ID is provided, create a new FAQ
            $faqSection = FAQSection::create($validatedData);
            $message = 'New FAQ section data created successfully';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $faqSection
        ], 200);
    }
}
