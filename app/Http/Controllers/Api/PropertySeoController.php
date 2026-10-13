<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use App\Models\PropertySeoSetting;
use Illuminate\Http\Request;

class PropertySeoController extends Controller
{
    public function show($propertyId)
    {
        if (!PropertyContent::where('property_id', $propertyId)->exists()) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }
        $seo = PropertySeoSetting::where('property_id', $propertyId)->first() ?? new PropertySeoSetting();
        return response()->json(['status' => true, 'data' => [
            'property_id' => (int) $propertyId, 'seo' => $seo->toSeoArray(),
        ]]);
    }

    public function update(Request $request, $propertyId)
    {
        if (!PropertyContent::where('property_id', $propertyId)->exists()) {
            return response()->json(['status' => false, 'message' => 'Property not found'], 404);
        }

        $rules = [];
        foreach (PropertySeoSetting::TEXT_FIELDS as $field) {
            $rules[$field] = 'sometimes|nullable|string|max:2000';
        }
        foreach (['canonical_url', 'og_image', 'og_url', 'twitter_image'] as $field) {
            $rules[$field] = 'sometimes|nullable|url:http,https|max:2048';
        }
        $rules['schema_markup'] = 'sometimes|nullable|string|json|max:100000';
        $rules['og_image_width'] = 'sometimes|nullable|integer|min:1|max:100000';
        $rules['og_image_height'] = 'sometimes|nullable|integer|min:1|max:100000';
        $rules['robots_index'] = 'sometimes|boolean';
        $rules['is_active'] = 'sometimes|boolean';
        $validated = $request->validate($rules);

        if (!$validated) {
            return response()->json(['status' => false, 'message' => 'Provide at least one SEO field'], 422);
        }

        $seo = PropertySeoSetting::updateOrCreate(['property_id' => $propertyId], $validated);
        return response()->json([
            'status' => true, 'message' => 'Property SEO settings saved successfully',
            'data' => ['property_id' => (int) $propertyId, 'seo' => $seo->toSeoArray()],
        ]);
    }
}
