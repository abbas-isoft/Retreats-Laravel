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
    public function storeOrUpdate(Request $request, $id = null)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:255',
            'subtitle' => 'required|string',
            'icon'     => 'nullable|file|image|max:5120',
            'color'    => 'nullable|string|max:20',
        ]);
        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('images', 'public');
            $validated['icon'] = basename($path);
        } elseif ($request->icon && is_string($request->icon)) {
            $validated['icon'] = basename($request->icon);
        }

        if ($id) {
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
            $featureBox = FeatureBox::create($validated);
            $message = 'Feature box created successfully.';
        }
        $featureBox->icon = url('storage/images/' . $featureBox->icon);

        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $featureBox
        ]);
    }
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
