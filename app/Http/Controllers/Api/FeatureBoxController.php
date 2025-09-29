<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureBox;
use Illuminate\Http\Request;

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

    /**
     * Create or update FeatureBox
     */
    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'icon'     => 'required|string',   // sirf filename (e.g. "location.png")
            'color'    => 'nullable|string|max:20',
        ]);

        if ($id) {
            // agar id di hui hai to update
            $featureBox = FeatureBox::find($id);
            if (! $featureBox) {
                return response()->json([
                    'status' => false,
                    'message' => 'Feature box not found.'
                ], 404);
            }

            $featureBox->update($validated);
            $message = 'Feature box updated successfully.';
        } else {
            // create new
            $featureBox = FeatureBox::create($validated);
            $message = 'Feature box created successfully.';
        }

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $featureBox
        ]);
    }
    public function destroy($id)
{
    $featureBox = FeatureBox::find($id);

    if (! $featureBox) {
        return response()->json([
            'status' => false,
            'message' => 'Feature box not found.'
        ], 404);
    }

    $featureBox->delete();

    return response()->json([
        'status' => true,
        'message' => 'Feature box deleted successfully.'
    ]);
}
}
