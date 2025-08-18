<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WhyPartnerBoxResource;
use App\Models\WhyPartnerBox;
use Illuminate\Http\Request;

class WhyPartnerBoxController extends Controller
{
    // GET all
    public function index()
    {
        $boxes = WhyPartnerBox::all();
        return WhyPartnerBoxResource::collection($boxes);
    }

    // POST multiple at once
    public function store(Request $request)
    {
        $data = $request->validate([
            'section_title' => 'required|string|max:255',
            'boxes'         => 'required|array|min:1',
            'boxes.*.box_title' => 'required|string|max:255',
            'boxes.*.box_description' => 'nullable|string',
        ]);

        $created = [];
        foreach ($data['boxes'] as $box) {
            $created[] = WhyPartnerBox::create([
                'section_title'   => $data['section_title'],
                'box_title'       => $box['box_title'],
                'box_description' => $box['box_description'] ?? null,
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Boxes created successfully',
            'data'    => WhyPartnerBoxResource::collection($created),
        ], 201);
    }
}
