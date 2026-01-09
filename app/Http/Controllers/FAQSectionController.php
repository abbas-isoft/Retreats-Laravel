<?php

namespace App\Http\Controllers;

use App\Models\FAQSection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FAQSectionController extends Controller
{
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

    public function storeFAQSection(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
        ]);

        if ($request->has('id') && $request->id != null) {
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
