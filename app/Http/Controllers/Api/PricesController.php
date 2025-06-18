<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PriceResource;
use App\Models\PropertyPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PricesController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $prices = PropertyPrice::latest()->get();

            if ($prices->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No pricing data found.',
                ]);
            }

            return response()->json([
                'status' => true,
                'Prices' => PriceResource::collection($prices),
            ]);
        } catch (\Exception $e) {
            Log::error('Price fetch failed: '.$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while fetching pricing data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
