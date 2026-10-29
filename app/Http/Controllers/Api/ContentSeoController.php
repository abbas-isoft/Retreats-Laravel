<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaveSeoRequest;
use App\Models\Collection;
use App\Models\Region;
use App\Services\SeoSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContentSeoController extends Controller
{
    public function updateCollection(
        SaveSeoRequest $request,
        Collection $collection,
        SeoSettingsService $service
    ): JsonResponse {
        return $this->update($request, $collection, $service, 'Collection');
    }

    public function updateRegion(
        SaveSeoRequest $request,
        Region $region,
        SeoSettingsService $service
    ): JsonResponse {
        return $this->update($request, $region, $service, 'Destination');
    }

    private function update(
        SaveSeoRequest $request,
        Model $owner,
        SeoSettingsService $service,
        string $label
    ): JsonResponse {
        $validated = $request->validated();
        if ($validated === []) {
            return response()->json([
                'status' => false,
                'message' => 'Provide at least one SEO field',
            ], 422);
        }

        $seo = DB::transaction(fn () => $service->update($owner, $validated));

        $cachePrefix = $owner instanceof Collection ? 'collection' : 'region';
        Cache::forget($cachePrefix === 'collection' ? 'collections' : 'regions');
        Cache::forget($cachePrefix.'.'.$owner->getKey());

        return response()->json([
            'status' => true,
            'message' => $label.' SEO settings saved successfully',
            'data' => [
                'id' => $owner->getKey(),
                'seo' => $seo->toSeoArray(),
            ],
        ]);
    }
}
