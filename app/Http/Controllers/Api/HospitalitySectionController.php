<?php

// app/Http/Controllers/Api/HospitalitySectionController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HospitalitySection;

class HospitalitySectionController extends Controller
{
    // ✅ GET API
    public function index()
    {
        $section = HospitalitySection::first(); // always only one record
        return response()->json([
            'status' => true,
            'data' => $section,
        ]);
    }

    // ✅ POST (Update or Create)
    public function update(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'required|string',
        ]);

        $section = HospitalitySection::first();

        if ($section) {
            $section->update($validated);
        } else {
            $section = HospitalitySection::create($validated);
        }

        return response()->json([
            'status' => true,
            'message' => 'Hospitality section updated successfully.',
            'data' => $section,
        ]);
    }
}

