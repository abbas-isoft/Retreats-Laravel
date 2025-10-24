<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeatureBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FeatureBoxController extends Controller
{
    public function index()
    {
        $boxes = FeatureBox::all()->map(function ($box) {
            // ✅ Agar icon already full URL hai, to dobara 'images/' prepend na karein
            if (!str_starts_with($box->icon, 'http')) {
                $box->icon = url('storage/images/' . $box->icon);
            }
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
            'icon'     => 'nullable|file|image|max:5120', // ✅ file/image allowed
            'color'    => 'nullable|string|max:20',
        ]);

        // ✅ handle image upload
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('images', 'public');
            $validated['icon'] = basename($path); // sirf filename store karein
        } elseif ($request->icon && is_string($request->icon)) {
            // agar admin ne string URL bheja hai (already uploaded)
            $validated['icon'] = basename($request->icon);
        }

        if ($id) {
            // ✅ update logic
            $featureBox = FeatureBox::find($id);
            if (!$featureBox) {
                return response()->json([
                    'status' => false,
                    'message' => 'Feature box not found.'
                ], 404);
            }

            $featureBox->update($validated);
            $message = 'Feature box updated successfully.';
        } else {
            // ✅ create logic
            $featureBox = FeatureBox::create($validated);
            $message = 'Feature box created successfully.';
        }

        // ✅ Return full image URL in response
        $featureBox->icon = url('storage/images/' . $featureBox->icon);

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $featureBox
        ]);
    }

    /**
     * Delete FeatureBox
     */
    public function destroy($id)
    {
        $featureBox = FeatureBox::find($id);

        if (!$featureBox) {
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
