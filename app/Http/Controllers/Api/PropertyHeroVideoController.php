<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyHeroVideoController extends Controller
{
    public function show($propertyId)
    {
        return $this->response($this->property($propertyId));
    }

    public function update(Request $request, $propertyId)
    {
        $property = $this->property($propertyId);
        $validated = $request->validate([
            'hero_video_url' => ['sometimes', 'nullable', 'string', 'max:2048', 'regex:~^storage/videos/[a-zA-Z0-9_-]+\.(mp4|webm)$~i'],
            'hero_video_thumbnail_url' => ['sometimes', 'nullable', 'string', 'max:2048', 'regex:~^storage/images/[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$~i'],
            'hero_video_enabled' => 'sometimes|boolean',
        ]);
        if (!$validated) {
            throw ValidationException::withMessages(['hero_video_enabled' => 'Provide at least one hero video setting.']);
        }
        $property->fill($validated);
        if ($property->hero_video_enabled && (!$property->hero_video_url || !$property->hero_video_thumbnail_url)) {
            throw ValidationException::withMessages(['hero_video_enabled' => 'A video and thumbnail are required before enabling hero video.']);
        }
        $property->save();
        return $this->response($property);
    }

    public function upload(Request $request, $propertyId)
    {
        $this->property($propertyId);
        $request->validate([
            'video' => 'required_without:thumbnail|file|mimetypes:video/mp4,video/webm|max:102400',
            'thumbnail' => 'required_without:video|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $data = [];
        foreach (['video' => 'videos', 'thumbnail' => 'images'] as $field => $directory) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store($directory, 'public');
                if (!$path) {
                    abort(500, 'Could not store uploaded file');
                }
                $data[$field . '_path'] = 'storage/' . $path;
                $data[$field . '_url'] = Storage::disk('public')->url($path);
            }
        }
        return response()->json(['status' => true, 'message' => 'Hero media uploaded; save the returned paths using PUT', 'data' => $data]);
    }

    private function property($propertyId): PropertyContent
    {
        return PropertyContent::where('property_id', $propertyId)->firstOrFail();
    }

    private function response(PropertyContent $property)
    {
        return response()->json(['status' => true, 'data' => [
            'property_id' => $property->property_id,
            'hero_video_url' => $property->hero_video_url,
            'hero_video_thumbnail_url' => $property->hero_video_thumbnail_url,
            'hero_video_enabled' => (bool) $property->hero_video_enabled,
            'hero_video' => $property->heroVideoData(),
        ]]);
    }
}
