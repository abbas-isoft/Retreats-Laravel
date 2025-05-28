<?php

namespace App\Http\Controllers;

use App\Models\PriceConfiguration;
use Carbon\Carbon;

class PriceListingController extends Controller
{
    public function index()
    {
        $configuration = PriceConfiguration::with(['fees', 'losEntries'])->first();

        return view('price-listing', compact('configuration'));
    }

    public function update()
    {
        $configuration = PriceConfiguration::first();

        if ($configuration) {
            $configuration->updated_at = Carbon::now();
            $configuration->save();

            return response()->json([
                'message' => 'Updated timestamp successfully!',
                'updated_at' => $configuration->updated_at->toDateTimeString(),
            ]);
        }

        return response()->json([
            'message' => 'No configuration found to update.',
        ], 404);
    }
}
