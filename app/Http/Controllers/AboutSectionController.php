<?php

namespace App\Http\Controllers;

use App\Models\AboutSection;
use App\Models\FAQSection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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
        try {
            $validatedData = $request->validate([
                'title' => 'required|string',
                'subtitle' => 'required|string',
                'video_url' => 'nullable|string',
            ]);

            $aboutSection = AboutSection::updateOrCreate(
                ['id' => 1],
                $validatedData
            );

            return response()->json([
                'status' => true,
                'message' => 'About section data updated successfully',
                'data' => $aboutSection
            ], 200);
        } catch (\Exception $e) {
            // Log the exception to get more information
            Log::error($e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function GetFaqsPage()
    // {

    //     $aboutSection = AboutSection::first();  // Assuming there is only one about section
    //     $faqSection = FAQSection::all();

    //     if (!$aboutSection) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'About section not found'
    //         ], 404);
    //     }


    //     if ($faqSection->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No FAQ data found'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'FAQ section data fetched successfully',
    //         'dalta' => $aboutSection,
    //         'data' => $faqSection
    //     ], 200);
    // }



    // GET: AboutSection + FAQs in structured format
    public function GetFaqsPage(): JsonResponse
    {
        $aboutSection = AboutSection::first();
        $faqSection = FAQSection::all();

        if (!$aboutSection) {
            return response()->json([
                'status' => false,
                'message' => 'About section not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'FAQs page fetched successfully',
            'data' => [
                'title' => $aboutSection->title,
                'subtitle' => $aboutSection->subtitle ?? '',
                'video_url' => env('ADMIN_URL') . '/' . ltrim($aboutSection->video_url ?? '', '/'),
                'faqs' => $faqSection->map(function($faq) {
                    return [
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'created_at' => $faq->created_at,
                        'updated_at' => $faq->updated_at,
                    ];
                }),
            ]
        ], 200);
    }
}
